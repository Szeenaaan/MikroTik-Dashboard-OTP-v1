<?php

namespace App\Services;

use App\Models\WifiUser;
use App\Models\HotspotSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusService
{
    public function createUser(string $username, string $password, Carbon $sessionEnd): bool
    {
        try {
            $sessionSeconds = (int) round(now()->diffInSeconds($sessionEnd));

            if ($sessionSeconds <= 0) {
                return false;
            }

            DB::connection('radius')->transaction(function () use ($username, $password, $sessionSeconds) {
                if (!$this->deleteUser($username)) {
                    throw new \RuntimeException('Failed to delete existing RADIUS user.');
                }

                DB::connection('radius')->table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $password,
                ]);

                DB::connection('radius')->table('radreply')->insert([
                    'username' => $username,
                    'attribute' => 'Session-Timeout',
                    'op' => ':=',
                    'value' => (string) $sessionSeconds,
                ]);
            });

            return true;
        } catch (\Throwable $e) {
            Log::channel('radius')->error(
                'RADIUS createUser failed.',
                [
                    'username' => $username,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return false;
        }
    }

    public function deleteUser(string $username): bool
    {
        try {
            DB::connection('radius')->table('radcheck')->where('username', $username)->delete();

            DB::connection('radius')->table('radreply')->where('username', $username)->delete();

            return true;
        } catch (\Throwable $e) {
            Log::channel('radius')->error(
                'RADIUS deleteUser failed.',
                [
                    'username' => $username,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return false;
        }
    }

    public function syncAccounting(): void
    {
        try {
            $connectState = DB::table('radius_sync_states')->where('key', 'radacct_connects')->first();

            $terminateState = DB::table('radius_sync_states')->where('key', 'radacct_terminates')->first();

            $connectSince = $connectState?->last_synced_at ?? now()->subMinutes(10);

            $connectLastId = $connectState?->last_synced_id ?? 0;

            $terminateSince = $terminateState?->last_synced_at ?? now()->subMinutes(10);

            $terminateLastId = $terminateState?->last_synced_id ?? 0;

            $this->processAccountingConnects($connectSince, $connectLastId);

            $this->processAccountingTerminates($terminateSince, $terminateLastId);

            $this->processExpiredSessions();
        } catch (\Throwable $e) {
            Log::channel('radius')->error(
                'RADIUS accounting sync failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    protected function processAccountingConnects($since, $lastId): void
    {
        $batchSize = 50;

        while (true) {
            $rows = DB::connection('radius')->table('radacct')
                ->where(function ($query) use ($since, $lastId) {
                    $query->where('acctstarttime', '>', $since)
                        ->orWhere(function ($query) use ($since, $lastId) {
                            $query->where('acctstarttime', '=', $since)->where('radacctid', '>', $lastId);
                        });
                })
                ->orderBy('acctstarttime')->orderBy('radacctid')->limit($batchSize)
                ->get(
                    [
                        'radacctid',
                        'callingstationid',
                        'acctstarttime',
                    ]
                );

            if ($rows->isEmpty()) {
                break;
            }

            $lastRow = $rows->last();

            DB::transaction(function () use ($rows, $lastRow) {
                foreach ($rows as $row) {
                    if (!$row->callingstationid) {
                        Log::channel('radius')->error(
                            'RADIUS accounting record has no calling station ID.',
                            [
                                'radacctid' => $row->radacctid,
                                'acctstarttime' => $row->acctstarttime,
                            ]
                        );

                        continue;
                    }

                    $mac = strtoupper($row->callingstationid);

                    WifiUser::where('mac_address', $mac)
                        ->where(function ($query) use ($row) {
                            $query->whereNull('last_login_at')
                                ->orWhere(
                                    'last_login_at',
                                    '<',
                                    $row->acctstarttime
                                );
                        })
                        ->update(['last_login_at' => $row->acctstarttime,]);
                }
                DB::table('radius_sync_states')
                    ->where('key', 'radacct_connects')
                    ->update([
                        'last_synced_at' => $lastRow->acctstarttime,
                        'last_synced_id' => $lastRow->radacctid,
                        'updated_at' => now(),
                    ]);
            });

            $since = $lastRow->acctstarttime;
            $lastId = $lastRow->radacctid;

            if ($rows->count() < $batchSize) {
                break;
            }
        }
    }

    protected function processExpiredSessions(): void
    {
        HotspotSession::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->where('session_end', '<=', now())
            ->chunkById(50, function ($hotspotSessions) {
                foreach ($hotspotSessions as $hotspotSession) {
                    $accounting = DB::connection('radius')
                        ->table('radacct')
                        ->where('username', $hotspotSession->radius_username)
                        ->where('nasipaddress', $hotspotSession->nas_ip)
                        ->where('callingstationid', $hotspotSession->mac)
                        ->where(
                            'acctstarttime',
                            '>=',
                            $hotspotSession->session_start
                        )
                        ->whereNotNull('acctstoptime')
                        ->latest('acctstoptime')
                        ->first(
                            [
                                'acctsessiontime',
                                'acctinputoctets',
                                'acctoutputoctets',
                            ]
                        );

                    if (!$accounting) {
                        continue;
                    }

                    DB::transaction(function () use ($hotspotSession, $accounting) {
                        $hotspotSession = HotspotSession::query()
                            ->whereKey($hotspotSession->id)->lockForUpdate()->first();

                        if (!$hotspotSession) {
                            return;
                        }

                        if (
                            $hotspotSession->status !== 'active' ||
                            !$hotspotSession->is_active
                        ) {
                            return;
                        }

                        $downloadBytes = (int) (
                            $accounting->acctoutputoctets ?? 0
                        );

                        $uploadBytes = (int) (
                            $accounting->acctinputoctets ?? 0
                        );

                        $downloadDelta = max(
                            0,
                            $downloadBytes -
                            (int) $hotspotSession->download_bytes
                        );

                        $uploadDelta = max(
                            0,
                            $uploadBytes -
                            (int) $hotspotSession->upload_bytes
                        );

                        $hotspotSession->update(
                            [
                                'session_time' => $accounting->acctsessiontime ?? 0,
                                'download_bytes' => $downloadBytes,
                                'upload_bytes' => $uploadBytes,
                                'status' => 'expired',
                                'is_active' => false,
                            ]
                        );

                        if ($downloadDelta > 0 || $uploadDelta > 0) {
                            $this->updateUserUsage(
                                $hotspotSession->mac,
                                $downloadDelta,
                                $uploadDelta
                            );
                        }
                    });
                }
            });
    }

    protected function processAccountingTerminates($since, $lastId): void
    {
        $batchSize = 50;

        while (true) {
            $rows = DB::connection('radius')
                ->table('radacct')
                ->whereNotNull('acctstoptime')
                ->where(function ($query) use ($since, $lastId) {
                    $query->where('acctstoptime', '>', $since)->orWhere(function ($query) use ($since, $lastId) {
                        $query->where('acctstoptime', '=', $since)->where('radacctid', '>', $lastId);
                    });
                })
                ->orderBy('acctstoptime')
                ->orderBy('radacctid')
                ->limit($batchSize)
                ->get(
                    [
                        'radacctid',
                        'username',
                        'nasipaddress',
                        'callingstationid',
                        'acctstarttime',
                        'acctstoptime',
                        'acctsessiontime',
                        'acctinputoctets',
                        'acctoutputoctets',
                    ]
                );

            if ($rows->isEmpty()) {
                break;
            }

            $lastRow = $rows->last();

            DB::transaction(function () use ($rows, $lastRow) {
                foreach ($rows as $row) {
                    if (!$row->callingstationid) {
                        Log::channel('radius')->error(
                            'RADIUS accounting termination record has no calling station ID.',
                            [
                                'radacctid' => $row->radacctid,
                                'acctstoptime' => $row->acctstoptime,
                            ]
                        );

                        continue;
                    }

                    $mac = strtoupper($row->callingstationid);

                    $hotspotSession = HotspotSession::query()
                        ->where('status', 'active')
                        ->where('is_active', true)
                        ->where('radius_username', $row->username)
                        ->where('nas_ip', $row->nasipaddress)
                        ->where('mac', $mac)
                        ->where('session_start', '<=', $row->acctstarttime)
                        ->latest('session_start')
                        ->lockForUpdate()
                        ->first();

                    if (!$hotspotSession) {
                        continue;
                    }

                    if ($hotspotSession->session_end->isPast()) {
                        continue;
                    }

                    $downloadBytes = (int) (
                        $row->acctoutputoctets ?? 0
                    );

                    $uploadBytes = (int) (
                        $row->acctinputoctets ?? 0
                    );

                    $downloadDelta = max(
                        0,
                        $downloadBytes - (int) $hotspotSession->download_bytes
                    );

                    $uploadDelta = max(
                        0,
                        $uploadBytes -
                        (int) $hotspotSession->upload_bytes
                    );

                    $hotspotSession->update(
                        [
                            'session_time' => $row->acctsessiontime ?? 0,
                            'download_bytes' => $downloadBytes,
                            'upload_bytes' => $uploadBytes,
                            'status' => 'terminated',
                            'is_active' => false,
                            'terminated_at' => $row->acctstoptime,
                        ]
                    );

                    if ($downloadDelta > 0 || $uploadDelta > 0) {
                        $this->updateUserUsage(
                            $hotspotSession->mac,
                            $downloadDelta,
                            $uploadDelta
                        );
                    }
                }

                DB::table('radius_sync_states')
                    ->where('key', 'radacct_terminates')
                    ->update([
                        'last_synced_at' => $lastRow->acctstoptime,
                        'last_synced_id' => $lastRow->radacctid,
                        'updated_at' => now(),
                    ]);
            });

            $since = $lastRow->acctstoptime;
            $lastId = $lastRow->radacctid;

            if ($rows->count() < $batchSize) {
                break;
            }
        }
    }

    protected function updateUserUsage(string $mac, int $downloadBytes, int $uploadBytes): void
    {
        $updated = WifiUser::where('mac_address', $mac)
            ->update(
                [
                    'download_bytes' => DB::raw(
                        'download_bytes + ' . $downloadBytes
                    ),
                    'upload_bytes' => DB::raw(
                        'upload_bytes + ' . $uploadBytes
                    ),
                ]
            );

        if ($updated === 0) {
            throw new \RuntimeException(
                "WifiUser not found for MAC: {$mac}"
            );
        }
    }
}
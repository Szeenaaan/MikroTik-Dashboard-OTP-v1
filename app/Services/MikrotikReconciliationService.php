<?php

namespace App\Services;

use App\Models\WifiUser;
use Illuminate\Support\Facades\Log;

class MikrotikReconciliationService
{
    public function __construct(
        protected MikrotikService $mikrotikService
    ) {
    }

    public function reconcile(): array
    {
        try {
            $result = $this->mikrotikService->getIpBindings();

            if (!$result['success']) {
                throw new \RuntimeException(
                    'Unable to retrieve MikroTik IP bindings.'
                );
            }

            $mikrotikUsers = [];

            foreach ($result['bindings'] as $binding) {
                if (
                    !isset($binding['mac-address']) ||
                    !isset($binding['type'])
                ) {
                    Log::channel('mikrotik')->error(
                        'Invalid MikroTik IP binding.',
                        [
                            'binding' => $binding,
                        ]
                    );

                    continue;
                }

                $mac = strtoupper($binding['mac-address']);
                $ipAddress = $binding['address'] ?? null;
                $type = $binding['type'];

                if (
                    !preg_match(
                        '/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/',
                        $mac
                    )
                ) {
                    Log::channel('mikrotik')->error(
                        'Invalid MAC address in MikroTik IP binding.',
                        [
                            'mac' => $mac,
                            'ip_address' => $ipAddress,
                            'type' => $type,
                        ]
                    );

                    continue;
                }

                if (!in_array($type, ['blocked', 'bypassed'], true)) {
                    continue;
                }

                if (isset($mikrotikUsers[$mac])) {
                    throw new \RuntimeException(
                        "Duplicate MikroTik special binding detected for MAC {$mac}."
                    );
                }

                $mikrotikUsers[$mac] = [
                    'ip_address' => $ipAddress,
                    'type' => $type,
                ];
            }

            $previouslySpecialMacs = WifiUser::query()
                ->whereIn('status', [
                    WifiUser::STATUS_BLOCKED,
                    WifiUser::STATUS_BYPASSED,
                ])
                ->pluck('mac_address')
                ->filter()
                ->map(fn($mac) => strtoupper($mac));

            $macAddresses = $previouslySpecialMacs
                ->merge(array_keys($mikrotikUsers))
                ->unique()
                ->values();

            if ($macAddresses->isEmpty()) {
                $summary = [
                    'checked' => 0,
                    'updated' => 0,
                    'unchanged' => 0,
                    'unknown' => 0,
                ];

                Log::channel('mikrotik')->info(
                    'MikroTik reconciliation completed.',
                    $summary
                );

                return $summary;
            }

            $wifiUsers = WifiUser::query()
                ->whereIn('mac_address', $macAddresses->all())
                ->get([
                    'id',
                    'mac_address',
                    'ip_address',
                    'status',
                    'is_active',
                    'permanent_access',
                ])
                ->keyBy(
                    fn(WifiUser $wifiUser) =>
                        strtoupper($wifiUser->mac_address)
                );

            $checked = 0;
            $updated = 0;
            $unchanged = 0;
            $unknown = 0;

            foreach ($macAddresses as $mac) {
                $wifiUser = $wifiUsers->get($mac);

                if (!$wifiUser) {
                    if (isset($mikrotikUsers[$mac])) {
                        $unknown++;

                        Log::channel('mikrotik')->error(
                            'WifiUser does not exist for MikroTik IP binding.',
                            [
                                'mac' => $mac,
                                'ip_address' => $mikrotikUsers[$mac]['ip_address'],
                                'type' => $mikrotikUsers[$mac]['type'],
                            ]
                        );
                    }

                    continue;
                }

                $checked++;

                if (isset($mikrotikUsers[$mac])) {
                    $binding = $mikrotikUsers[$mac];

                    $changed = $this->syncSpecialUser(
                        $wifiUser,
                        $binding['ip_address'],
                        $binding['type']
                    );
                } else {
                    $changed = $this->syncActiveUser($wifiUser);
                }

                if ($changed) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            $summary = [
                'checked' => $checked,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'unknown' => $unknown,
            ];

            Log::channel('mikrotik')->info(
                'MikroTik reconciliation completed.',
                $summary
            );

            return $summary;
        } catch (\Throwable $e) {
            Log::channel('mikrotik')->error(
                'MikroTik reconciliation failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    private function syncSpecialUser(
        WifiUser $wifiUser,
        ?string $ipAddress,
        string $type
    ): bool {
        if ($type === 'blocked') {
            $status = WifiUser::STATUS_BLOCKED;
            $isActive = false;
            $permanentAccess = false;

            $blockedAt = $wifiUser->status === WifiUser::STATUS_BLOCKED
                ? $wifiUser->blocked_at
                : now();
        } elseif ($type === 'bypassed') {
            $status = WifiUser::STATUS_BYPASSED;
            $isActive = true;
            $permanentAccess = true;
            $blockedAt = null;
        } else {
            throw new \InvalidArgumentException(
                'Invalid MikroTik special binding type.'
            );
        }

        $newIpAddress = $ipAddress ?? $wifiUser->ip_address;

        if (
            $wifiUser->ip_address === $newIpAddress &&
            $wifiUser->status === $status &&
            $wifiUser->is_active === $isActive &&
            $wifiUser->permanent_access === $permanentAccess &&
            $wifiUser->blocked_at == $blockedAt
        ) {
            return false;
        }

        $wifiUser->update([
            'ip_address' => $newIpAddress,
            'status' => $status,
            'is_active' => $isActive,
            'permanent_access' => $permanentAccess,
            'blocked_at' => $blockedAt,
        ]);

        return true;
    }

    private function syncActiveUser(WifiUser $wifiUser): bool
    {
        if (
            $wifiUser->status === WifiUser::STATUS_ACTIVE &&
            $wifiUser->is_active === true &&
            $wifiUser->permanent_access === false &&
            $wifiUser->blocked_at === null
        ) {
            return false;
        }

        $wifiUser->update([
            'status' => WifiUser::STATUS_ACTIVE,
            'is_active' => true,
            'permanent_access' => false,
            'blocked_at' => null,
        ]);

        return true;
    }
}
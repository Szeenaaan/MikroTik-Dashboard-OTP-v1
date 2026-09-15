<?php

namespace App\Services;

use App\Models\WifiUser;
use App\Models\HotspotSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardService
{

    public function __construct(
        private MikrotikService $mikrotikService,
        private AuditLogService $auditLogService
    ) {
    }


    public function getDataUsage(DataUsageQueryDto $dto): array
    {
        try {
            $downloadData = HotspotSession::sum('download_bytes');
            $uploadData = HotspotSession::sum('upload_bytes');

            return match ($dto->type) {

                'download' => [
                    'type' => 'download',
                    'data' => $downloadData,
                ],

                'upload' => [
                    'type' => 'upload',
                    'data' => $uploadData,
                ],

                'total' =>
                    [
                        'type' => 'total',
                        'download_data' => $downloadData,
                        'upload_data' => $uploadData,
                        'total_data' => $downloadData + $uploadData,
                    ],

                default =>
                    [
                        'type' => 'total',
                        'download_data' => $downloadData,
                        'upload_data' => $uploadData,
                        'total_data' => $downloadData + $uploadData,
                    ],
            };
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to load data usage.',
                [
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to load data usage',];
        }
    }

    public function getDateData(DataUsageQueryDto $dto): array
    {
        try {
            $startDate = match ($dto->period) {
                'h' => now()->subHour(),
                'd' => now()->subDay(),
                '3d' => now()->subDays(3),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => now()->subDay(),
            };

            $data = HotspotSession::where('session_start', '>=', $startDate)
                ->selectRaw('SUM(download_bytes) as download_data,SUM(upload_bytes) as upload_data')->first();

            $downloadData = $data->download_data ?? 0;
            $uploadData = $data->upload_data ?? 0;

            return match ($dto->type) {

                'download' => [
                    'type' => 'download',
                    'period' => $dto->period,
                    'data' => $downloadData,
                ],

                'upload' => [
                    'type' => 'upload',
                    'period' => $dto->period,
                    'data' => $uploadData,
                ],

                'total' =>
                    [
                        'type' => 'total',
                        'period' => $dto->period,
                        'download_data' => $downloadData,
                        'upload_data' => $uploadData,
                        'total_data' => $downloadData + $uploadData,
                    ],

                default =>
                    [
                        'type' => 'total',
                        'period' => $dto->period,
                        'download_data' => $downloadData,
                        'upload_data' => $uploadData,
                        'total_data' => $downloadData + $uploadData,
                    ],
            };
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to load date data.',
                [
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to load date data',];
        }
    }

    public function getUsers(?string $search = null, ?string $status = null, ?string $fromDate = null, ?string $toDate = null, string $order = 'desc'): LengthAwarePaginator|array
    {
        try {
            $query = WifiUser::query()->select([
                'id',
                'mac_address',
                'phone_number',
                'ip_address',
                'last_login_at',
                'download_bytes',
                'upload_bytes',
                'is_active',
                'status',
                'permanent_access',
                'session_count',
            ]);

            if ($fromDate && $toDate && $fromDate > $toDate) {
                return ['error' => 'From date cannot be later than To date.',];
            }


            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('mac_address', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
            }

            if ($fromDate) {
                $query->whereDate('last_login_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('last_login_at', '<=', $toDate);
            }

            $query->orderBy('last_login_at', $order === 'asc' ? 'asc' : 'desc');

            return $query->paginate(10);
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to load user data.',
                [

                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to load user data',];
        }
    }

    public function blockUser(int $id): array
    {
        try {
            $wifiUser = WifiUser::find($id);

            if (!$wifiUser) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'User not found.',];
            }

            if ($wifiUser->status === WifiUser::STATUS_BLOCKED) {
                return ['success' => false, 'statuscode' => 409, 'message' => 'User is already blocked or Try to refresh the browser.',];
            }

            $result = $this->mikrotikService->blockUser($wifiUser->mac_address);

            if (!$result['success']) {
                return $result;
            }

            $wifiUser->update(
                [
                    'is_active' => false,
                    'status' => WifiUser::STATUS_BLOCKED,
                    'blocked_at' => now(),
                ]
            );

            try {
                $this->auditLogService->log('block', $wifiUser->mac_address);
            } catch (\Throwable $e) {
                Log::channel('audit')->error(
                    'Unable to save audit data',
                    [
                        'user_id' => $id,
                        'user' => $wifiUser->mac_address,
                        'admin_id' => auth()->id(),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'User blocked successfully.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to block user.',
                [
                    'user_id' => $id,
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to block user',];
        }
    }

    public function unblockUser(int $id): array
    {
        try {
            $wifiUser = WifiUser::find($id);

            if (!$wifiUser) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'User not found.',];
            }

            if ($wifiUser->status !== WifiUser::STATUS_BLOCKED) {
                return ['success' => false, 'statuscode' => 409, 'message' => 'User is not blocked or Try to refresh the browser.',];
            }

            $result = $this->mikrotikService->unblockUser($wifiUser->mac_address);

            if (!$result['success']) {
                return $result;
            }

            $wifiUser->update(
                [
                    'is_active' => true,
                    'status' => WifiUser::STATUS_ACTIVE,
                    'blocked_at' => null,
                ]
            );

            try {
                $this->auditLogService->log('unblock', $wifiUser->mac_address);
            } catch (\Throwable $e) {
                Log::channel('audit')->error(
                    'Unable to save audit data',
                    [
                        'user_id' => $id,
                        'user' => $wifiUser->mac_address,
                        'admin_id' => auth()->id(),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'User unblocked successfully.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to Unblock user.',
                [
                    'user_id' => $id,
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to Unblock user',];
        }
    }

    public function getChartData(DataUsageQueryDto $dto): array
    {
        try {
            $now = now();

            $startDate = match ($dto->period) {
                'h' => $now->copy()->subHour(),
                'd' => $now->copy()->subDay(),
                '3d' => $now->copy()->subDays(3),
                'week' => $now->copy()->subWeek(),
                'month' => $now->copy()->subMonth(),
                default => $now->copy()->subDay(),
            };

            $interval = match ($dto->period) {
                'h' => 300,        // 5 minutes
                'd' => 3600,       // 1 hour
                '3d' => 21600,     // 6 hours
                'week' => 86400,   // 1 day
                'month' => 86400,  // 1 day
                default => 3600,
            };

            $data = HotspotSession::where('session_start', '>=', $startDate)
                ->selectRaw(
                    "
            FLOOR(UNIX_TIMESTAMP(session_start) / ?) * ? as bucket,
            SUM(download_bytes) as download,
            SUM(upload_bytes) as upload
            ",
                    [$interval, $interval]
                )
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get()
                ->keyBy('bucket');

            $chartData = [];

            $firstBucket = intdiv($startDate->timestamp, $interval) * $interval;
            $lastBucket = intdiv($now->timestamp, $interval) * $interval;

            for ($timestamp = $firstBucket; $timestamp <= $lastBucket; $timestamp += $interval) {

                $bucket = $data->get($timestamp);

                $download = (int) ($bucket->download ?? 0);
                $upload = (int) ($bucket->upload ?? 0);

                $chartData[] = [
                    'time' => date('Y-m-d H:i:s', $timestamp),
                    'download' => $download,
                    'upload' => $upload,
                    'total' => $download + $upload,
                ];
            }

            return $chartData;
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to load chart data.',
                [
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to load chart data',];
        }
    }

    public function getSessions(
        ?string $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $order = 'desc',
        ?string $search = null
    ): LengthAwarePaginator|array {

        try {
            $query = HotspotSession::query()->select([
                'id',
                'mac',
                'radius_username',
                'ip',
                'session_start',
                'session_end',
                'download_bytes',
                'upload_bytes',
                'session_time',
                'status',
                'is_active',
            ]);
            
            if ($fromDate && $toDate && $fromDate > $toDate) {
                return ['error' => 'From date cannot be later than To date.',];
            }

            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('mac', 'like', '%' . $search . '%')
                        ->orWhere('radius_username', 'like', '%' . $search . '%')
                        ->orWhere('ip', 'like', '%' . $search . '%');
                });
            }

            // Status
            if ($status) {
                $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
            }

            if ($fromDate) {
                $query->whereDate('session_start', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('session_start', '<=', $toDate);
            }

            $query->orderBy('session_start', $order === 'asc' ? 'asc' : 'desc');

            return $query->paginate(10);
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to load sessions table.',
                [
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to load sessions table',];
        }
    }

    public function terminateSession(int $id): array
    {
        try {
            $session = HotspotSession::find($id);

            if (!$session) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'Session not found.',];
            }

            if ($session->status !== 'active' || !$session->is_active) {
                return ['success' => false, 'statuscode' => 409, 'message' => 'Session is not active.',];
            }

            $result = $this->mikrotikService->terminateSession($session->mac);

            if (!$result['success']) {
                return $result;
            }

            $session->update(
                [
                    'status' => 'terminated',
                    'terminated_at' => now(),
                    'is_active' => false,
                    'session_end' => now(),
                ]
            );


            try {
                $this->auditLogService->log('terminate', $session->mac);
            } catch (\Throwable $e) {
                Log::channel('audit')->error(
                    'Unable to save audit data',
                    [
                        'user_id' => $id,
                        'session' => $session->session_id,
                        'admin_id' => auth()->id(),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'Session terminated successfully.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to terminate session.',
                [
                    'user_id' => $id,
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to terminate session.',];
        }
    }

    public function bypassUser(int $id): array
    {
        try {
            $wifiUser = WifiUser::find($id);

            if (!$wifiUser) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'User not found.',];
            }
            if ($wifiUser->status === WifiUser::STATUS_BLOCKED) {
                return ['success' => false, 'statuscode' => 400, 'message' => "Can't give permanent access because this user is blocked.",];
            }
            if ($wifiUser->permanent_access) {
                return ['success' => false, 'statuscode' => 400, 'message' => 'User already has permanent access.',];
            }

            $result = $this->mikrotikService->bypassUser(
                $wifiUser->mac_address
            );

            if (!$result['success']) {
                return $result;
            }

            $wifiUser->update(
                [
                    'permanent_access' => true,
                    'status' => WifiUser::STATUS_BYPASSED,
                    'is_active' => true,
                ]
            );

            try {
                $this->auditLogService->log('permanent access', $wifiUser->mac_address);
            } catch (\Throwable $e) {
                Log::channel('audit')->error(
                    'Unable to save audit data',
                    [
                        'user_id' => $id,
                        'user' => $wifiUser->mac_address,
                        'admin_id' => auth()->id(),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'Permanent access enabled successfully.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to give permanent access to user.',
                [
                    'user_id' => $id,
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to enable permanent access.',];
        }
    }

    public function removeBypassUser(int $id): array
    {
        try {
            $wifiUser = WifiUser::find($id);

            if (!$wifiUser) {
                return ['success' => false, 'statuscode' => 404, 'message' => 'User not found.',];
            }

            if (!$wifiUser->permanent_access) {
                return ['success' => false, 'statuscode' => 400, 'message' => 'User does not have permanent access.',];
            }

            $result = $this->mikrotikService->removeBypassUser(
                $wifiUser->mac_address
            );

            if (!$result['success']) {
                return $result;
            }

            $wifiUser->update(
                [
                    'permanent_access' => false,
                    'status' => WifiUser::STATUS_ACTIVE,
                    'is_active' => true,
                ]
            );

            try {
                $this->auditLogService->log('remove access', $wifiUser->mac_address);
            } catch (\Throwable $e) {
                Log::channel('audit')->error(
                    'Unable to save audit data',
                    [
                        'user_id' => $id,
                        'user' => $wifiUser->mac_address,
                        'admin_id' => auth()->id(),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                );
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'Permanent access removed successfully.',];
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Unable to remove permanent access of user.',
                [
                    'user_id' => $id,
                    'admin_id' => auth()->id(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return ['success' => false, 'statuscode' => 500, 'message' => 'Unable to remove permanent access.',];
        }
    }

}

class DataUsageQueryDto
{
    public function __construct
    (
        public ?string $type = null,
        public ?string $period = null,
    ) {
    }
}
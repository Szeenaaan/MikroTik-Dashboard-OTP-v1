<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OtpSession;
use Illuminate\Support\Facades\Log;
use App\Models\HotspotSession;

class CleanupService
{
    public function cleanup(): void
    {
        try {
            $this->cleanupOtpSessions();
            $this->cleanupAuditLogs();
            $this->deleteOldHotspotSessions();
        } catch (\Throwable $e) {
            Log::channel('error')->error(
                'Database cleanup failed.',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    protected function cleanupOtpSessions(): void
    {
        $cutoff = now()->subDays(14);

        do {
            $deleted = OtpSession::query()->where('created_at', '<', $cutoff)->limit(100)->delete();
        } while ($deleted > 0);
    }

    protected function cleanupAuditLogs(): void
    {
        $cutoff = now()->subDays(14);

        do {
            $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->limit(100)->delete();
        } while ($deleted > 0);
    }
    protected function deleteOldHotspotSessions(): void
    {
        $cutoff = now()->subDays(30);

        do {
            $deleted = HotspotSession::query()
                ->where('session_end', '<', $cutoff)
                ->where('is_active', false)
                ->limit(100)
                ->delete();
        } while ($deleted > 0);
    }
}
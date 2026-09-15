<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function log(
        string $operation,
        string $user
    ): void {
        AuditLog::create([
            'admin_id' => auth()->id(),
            'operation' => $operation,
            'user' => $user,
            'created_at' => now(),
        ]);
    }
}
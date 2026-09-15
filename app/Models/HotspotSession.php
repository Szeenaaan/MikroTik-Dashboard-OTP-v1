<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotSession extends Model
{
    protected $fillable = [
        'wifi_user_id',
        'otp_session_id',
        'radius_username',
        'radius_password',
        'mac',
        'ip',
        'nas_ip',
        'session_id',
        'session_start',
        'session_end',
        'terminated_at',
        'download_bytes',
        'upload_bytes',
        'session_time',
        'status',
        'is_active',
        'data_limit',
    ];

    protected $hidden = [
        'radius_password',
    ];

    protected function casts(): array
    {
        return [
            'session_start' => 'datetime',
            'session_end' => 'datetime',
            'terminated_at' => 'datetime',
            'download_bytes' => 'integer',
            'upload_bytes' => 'integer',
            'session_time' => 'integer',
            'is_active' => 'boolean',
            'data_limit' => 'integer',
        ];
    }

    public function wifiUser(): BelongsTo
    {
        return $this->belongsTo(WifiUser::class);
    }

    public function otpSession(): BelongsTo
    {
        return $this->belongsTo(OtpSession::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function terminate(): void
    {
        $this->update([
            'status' => 'terminated',
            'is_active' => false,
            'terminated_at' => now(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForMac($query, string $mac)
    {
        return $query->where('mac', $mac);
    }
}
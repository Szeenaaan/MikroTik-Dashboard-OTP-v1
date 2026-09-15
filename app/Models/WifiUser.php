<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiUser extends Model
{
    use HasFactory;

    /**
     * Hardcoded status values (per current design — no enum).
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_BYPASSED = 'BYPASSED';

    protected $fillable = [
        'mac_address',
        'phone_number',
        'old_phone_number',
        'ip_address',
        'first_login_at',
        'last_login_at',
        'download_bytes',
        'upload_bytes',
        'is_active',
        'status',
        'permanent_access',
        'blocked_at',
        'blocked_reason',
        'session_count',
    ];

    protected $casts = [
        'first_login_at' => 'datetime',
        'last_login_at' => 'datetime',
        'blocked_at' => 'datetime',
        'download_bytes' => 'integer',
        'upload_bytes' => 'integer',
        'session_count' => 'integer',
        'is_active' => 'boolean',
        'permanent_access' => 'boolean',
    ];

    /**
     * All hotspot sessions (login events) for this device.
     */
    public function hotspotSessions()
    {
        return $this->hasMany(HotspotSession::class);
    }

    /**
     * Convenience scopes for dashboard filtering.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OtpSession extends Model
{
    protected $fillable = [
        'phone',
        'otp',
        'mac',
        'ip',
        'router_name',
        'router_ip',
        'router_host',
        'link_login',
        'link_orig',
        'status',
        'attempts',
        'resend_count',
        'last_resend_at',
        'expires_at',
        'verified_at',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'last_resend_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function hotspotSession(): HasOne
    {
        return $this->hasOne(HotspotSession::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_agent',
        'mac_address',
        'ip_address',
        'status',
        'is_active',
        'activated_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
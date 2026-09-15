<?php

namespace App\Exports;

use App\Models\WifiUser;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private ?string $search = null,
        private ?string $status = null,
        private ?string $fromDate = null,
        private ?string $toDate = null,
        private string $order = 'desc',
    ) {
    }

    public function query(): Builder
    {
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
            'session_count',
        ]);

        if ($this->search) {
            $query->where(function ($query) {
                $query->where('mac_address', 'like', "%{$this->search}%")
                    ->orWhere('phone_number', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->fromDate) {
            $query->whereDate('last_login_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('last_login_at', '<=', $this->toDate);
        }

        return $query->orderBy(
            'last_login_at',
            $this->order === 'asc' ? 'asc' : 'desc'
        );
    }

    public function headings(): array
    {
        return [
            'MAC Address',
            'Phone Number',
            'IP Address',
            'Last Login',
            'Download Bytes',
            'Upload Bytes',
            'Active',
            'Status',
            'Session Count',
        ];
    }

    public function map($user): array
    {
        return [
            $user->mac_address,
            $user->phone_number,
            $user->ip_address,
            $user->last_login_at,
            $user->download_bytes,
            $user->upload_bytes,
            $user->is_active ? 'Yes' : 'No',
            $user->status,
            $user->session_count,
        ];
    }
}
<?php

namespace App\Exports;

use App\Models\HotspotSession;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SessionsExport implements FromQuery, WithHeadings, WithMapping
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
        $query = HotspotSession::query()->select([
            'id',
            'mac',
            'ip',
            'nas_ip',
            'session_id',
            'session_start',
            'session_end',
            'download_bytes',
            'upload_bytes',
            'session_time',
            'status',
            'is_active',
        ]);

        if ($this->search) {
            $query->where(function ($query) {
                $query->where('mac', 'like', "%{$this->search}%")
                    ->orWhere('ip', 'like', "%{$this->search}%")
                    ->orWhere('nas_ip', 'like', "%{$this->search}%")
                    ->orWhere('session_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->fromDate) {
            $query->whereDate('session_start', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('session_start', '<=', $this->toDate);
        }

        return $query->orderBy(
            'session_start',
            $this->order === 'asc' ? 'asc' : 'desc'
        );
    }

    public function headings(): array
    {
        return [
            'MAC Address',
            'IP Address',
            'NAS IP',
            'Session ID',
            'Session Start',
            'Session End',
            'Download Bytes',
            'Upload Bytes',
            'Session Time',
            'Status',
        ];
    }

    public function map($session): array
    {
        return [
            $session->mac,
            $session->ip,
            $session->nas_ip,
            $session->session_id,
            $session->session_start,
            $session->session_end,
            $session->download_bytes,
            $session->upload_bytes,
            $session->session_time,
            $session->status,
        ];
    }
}
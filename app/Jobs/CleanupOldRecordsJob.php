<?php

namespace App\Jobs;

use App\Services\CleanupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class CleanupOldRecordsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 120;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('old-records-cleanup'))
                ->releaseAfter(10)
                ->expireAfter(180),
        ];
    }

    public function handle(CleanupService $cleanupService): void
    {
        $cleanupService->cleanup();
    }
}
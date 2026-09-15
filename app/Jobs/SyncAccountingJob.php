<?php

namespace App\Jobs;

use App\Services\RadiusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncAccountingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 120;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(RadiusService $radiusService): void
    {
        $radiusService->syncAccounting();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('radius-accounting-sync'))->releaseAfter(10)->expireAfter(180),
        ];
    }
}
<?php

namespace App\Jobs;

use App\Services\MikrotikReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ReconcileMikrotikJob implements ShouldQueue
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
            (new WithoutOverlapping('mikrotik-reconciliation'))
                ->expireAfter(180)
                ->dontRelease(),
        ];
    }

    public function handle(
        MikrotikReconciliationService $reconciliationService
    ): void {
        $reconciliationService->reconcile();
    }
}
<?php

namespace App\Listeners;

use App\Events\TestPurchasePaid;
use App\Services\Admin\AdminFinancialStatsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAdminFinancialStatsAfterTestPurchase implements ShouldQueue
{
    use InteractsWithQueue;
    public int $tries = 2;
    public bool $afterCommit = true;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';

    public function __construct(
        private readonly AdminFinancialStatsService $adminFinancialStatsService,
    ) {}

    public function handle(TestPurchasePaid $event): void
    {
        $this->adminFinancialStatsService->refreshAfterPaidTestPurchase(
            purchaseId: $event->purchaseId,
            paymentAttemptId: $event->paymentAttemptId,
            stripeEventId: $event->stripeEventId,
        );
    }

    public function backoff(): array
    {
        return [10];
    }

    public function failed(TestPurchasePaid $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed to update admin financial summary tables after paid test purchase', [
            'purchase_id' => $event->purchaseId,
            'payment_attempt_id' => $event->paymentAttemptId,
            'stripe_event_id' => $event->stripeEventId,
            'message' => $exception->getMessage(),
        ]);
    }
}

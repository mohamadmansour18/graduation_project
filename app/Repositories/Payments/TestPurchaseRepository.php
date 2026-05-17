<?php

namespace App\Repositories\Payments;

use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;

class TestPurchaseRepository
{
    public function userHasPaidPurchase(int $testId, int $buyerUserId): bool
    {
        return DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('buyer_user_id', $buyerUserId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function createPendingPurchase(array $data): int
    {
        return DB::table('test_purchases')->insertGetId([
            'test_id' => $data['test_id'],
            'buyer_user_id' => $data['buyer_user_id'],
            'seller_user_id' => $data['seller_user_id'],

            'gross_amount' => $data['gross_amount'],
            'platform_fee_amount' => $data['platform_fee_amount'],
            'seller_net_amount' => $data['seller_net_amount'],
            'currency' => $data['currency'],

            'payment_provider' => $data['payment_provider'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_status' => PaymentStatus::Pending->value,
            'purchased_at' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updatePaymentReference(int $purchaseId, string $paymentReference): void
    {
        DB::table('test_purchases')
            ->where('id', $purchaseId)
            ->update([
                'payment_reference' => $paymentReference,
                'updated_at' => now(),
            ]);
    }

    public function markAsFailed(int $purchaseId): void
    {
        DB::table('test_purchases')
            ->where('id', $purchaseId)
            ->where('payment_status', PaymentStatus::Pending->value)
            ->update([
                'payment_status' => PaymentStatus::Failed->value,
                'updated_at' => now(),
            ]);
    }
}

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

    public function preparePurchaseRecord(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $existingPurchase = DB::table('test_purchases')
                ->where('test_id', $data['test_id'])
                ->where('buyer_user_id', $data['buyer_user_id'])
                ->lockForUpdate()
                ->first();

            if (! $existingPurchase) {
                $purchaseId = DB::table('test_purchases')->insertGetId([
                    'test_id' => $data['test_id'],
                    'buyer_user_id' => $data['buyer_user_id'],
                    'seller_user_id' => $data['seller_user_id'],

                    'gross_amount' => $data['gross_amount'],
                    'platform_fee_amount' => $data['platform_fee_amount'],
                    'seller_net_amount' => $data['seller_net_amount'],
                    'currency' => $data['currency'],

                    'payment_provider' => $data['payment_provider'],
                    'payment_reference' => null,
                    'payment_status' => PaymentStatus::Pending->value,
                    'purchased_at' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return DB::table('test_purchases')
                    ->where('id', $purchaseId)
                    ->first();
            }

            if ($existingPurchase->payment_status === PaymentStatus::Paid->value) {
                return $existingPurchase;
            }

            DB::table('test_purchases')
                ->where('id', $existingPurchase->id)
                ->update([
                    'seller_user_id' => $data['seller_user_id'],
                    'gross_amount' => $data['gross_amount'],
                    'platform_fee_amount' => $data['platform_fee_amount'],
                    'seller_net_amount' => $data['seller_net_amount'],
                    'currency' => $data['currency'],
                    'payment_provider' => $data['payment_provider'],
                    'payment_status' => PaymentStatus::Pending->value,
                    'purchased_at' => null,
                    'updated_at' => now(),
                ]);

            return DB::table('test_purchases')
                ->where('id', $existingPurchase->id)
                ->first();
        });
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

    public function markPendingPurchaseAsPaidByReference(string $paymentReference): array
    {
        return DB::transaction(function () use ($paymentReference) {
            $purchase = DB::table('test_purchases')
                ->where('payment_reference', $paymentReference)
                ->lockForUpdate()
                ->first();

            if (! $purchase) {
                return [
                    'purchase' => null,
                    'was_marked_as_paid' => false,
                    'reason' => 'not_found',
                ];
            }

            if ($purchase->payment_status === PaymentStatus::Paid->value) {
                return [
                    'purchase' => $purchase,
                    'was_marked_as_paid' => false,
                    'reason' => 'already_paid',
                ];
            }

            if ($purchase->payment_status !== PaymentStatus::Pending->value) {
                return [
                    'purchase' => $purchase,
                    'was_marked_as_paid' => false,
                    'reason' => 'not_pending',
                ];
            }

            DB::table('test_purchases')
                ->where('id', $purchase->id)
                ->update([
                    'payment_status' => PaymentStatus::Paid->value,
                    'purchased_at' => now(),
                    'updated_at' => now(),
                ]);

            $updatedPurchase = DB::table('test_purchases')
                ->where('id', $purchase->id)
                ->first();

            return [
                'purchase' => $updatedPurchase,
                'was_marked_as_paid' => true,
                'reason' => 'marked_as_paid',
            ];
        });
    }

    public function markPendingPurchaseAsCancelledByReference(string $paymentReference): void
    {
        DB::table('test_purchases')
            ->where('payment_reference', $paymentReference)
            ->where('payment_status', PaymentStatus::Pending->value)
            ->update([
                'payment_status' => PaymentStatus::Cancelled->value,
                'updated_at' => now(),
            ]);
    }

    public function markAsPaidFromAttempt(object $purchase, object $attempt): object
    {
        return DB::transaction(function () use ($purchase, $attempt) {
            $lockedPurchase = DB::table('test_purchases')
                ->where('id', $purchase->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPurchase) {
                return [
                    'purchase' => $purchase,
                    'was_already_paid' => false,
                    'was_marked_as_paid_now' => false,
                ];
            }

            if ($lockedPurchase->payment_status === \App\Enums\Payments\PaymentStatus::Paid->value) {
                return [
                    'purchase' => $lockedPurchase,
                    'was_already_paid' => true,
                    'was_marked_as_paid_now' => false,
                ];
            }

            DB::table('test_purchases')
                ->where('id', $lockedPurchase->id)
                ->update([
                    'payment_provider' => $attempt->payment_provider,
                    'payment_reference' => $attempt->provider_reference,
                    'payment_status' => \App\Enums\Payments\PaymentStatus::Paid->value,
                    'purchased_at' => now(),
                    'updated_at' => now(),
                ]);

            $updatedPurchase = DB::table('test_purchases')
                ->where('id', $lockedPurchase->id)
                ->first();

            return [
                'purchase' => $updatedPurchase,
                'was_already_paid' => false,
                'was_marked_as_paid_now' => true,
            ];
        });
    }

    public function markAsCancelledIfNoActiveAttempts(int $purchaseId, bool $hasActiveAttempt): void
    {
        if ($hasActiveAttempt) {
            return;
        }

        DB::table('test_purchases')
            ->where('id', $purchaseId)
            ->where('payment_status', PaymentStatus::Pending->value)
            ->update([
                'payment_status' => PaymentStatus::Cancelled->value,
                'updated_at' => now(),
            ]);
    }

    public function findById(int $purchaseId): ?object
    {
        return DB::table('test_purchases')
            ->where('id', $purchaseId)
            ->first();
    }

}

<?php

namespace App\Repositories\Payments;

use App\Enums\Payments\PaymentAttemptStatus;
use Illuminate\Support\Facades\DB;

class PaymentAttemptRepository
{
    public function createPendingAttempt(array $data): object
    {
        $attemptId = DB::table('payment_attempts')->insertGetId([
            'test_purchase_id' => $data['test_purchase_id'],
            'payment_provider' => $data['payment_provider'],
            'provider_reference' => null,
            'provider_payment_intent_reference' => null,
            'checkout_url' => null,

            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'source_amount' => $data['source_amount'] ?? null,
            'source_currency' => $data['source_currency'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? null,
            'exchange_rate_provider' => $data['exchange_rate_provider'] ?? null,
            'exchange_rate_fetched_at' => $data['exchange_rate_fetched_at'] ?? null,
            'exchange_rate_expires_at' => $data['exchange_rate_expires_at'] ?? null,
            'exchange_rate_is_fallback' => $data['exchange_rate_is_fallback'] ?? false,
            'status' => PaymentAttemptStatus::Pending->value,

            'expires_at' => $data['expires_at'],
            'metadata' => isset($data['metadata'])
                ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE)
                : null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('payment_attempts')->where('id', $attemptId)->first();
    }

    public function attachStripeCheckoutSession(
        int $attemptId,
        string $checkoutSessionId,
        string $checkoutUrl,
        ?string $paymentIntentId,
        ?int $expiresAt
    ): void {
        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->update([
                'provider_reference' => $checkoutSessionId,
                'provider_payment_intent_reference' => $paymentIntentId,
                'checkout_url' => $checkoutUrl,
                'expires_at' => $expiresAt ? now()->setTimestamp($expiresAt) : null,
                'updated_at' => now(),
            ]);
    }

    public function findByProviderReference(string $providerReference): ?object
    {
        return DB::table('payment_attempts')
            ->where('provider_reference', $providerReference)
            ->first();
    }

    public function findByPaymentIntentReference(string $paymentIntentReference): ?object
    {
        return DB::table('payment_attempts')
            ->where('provider_payment_intent_reference', $paymentIntentReference)
            ->first();
    }

    public function findById(int $attemptId): ?object
    {
        return DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->first();
    }

    public function findForBuyer(int $attemptId, int $buyerUserId): ?object
    {
        return DB::table('payment_attempts')
            ->join('test_purchases', 'test_purchases.id', '=', 'payment_attempts.test_purchase_id')
            ->where('payment_attempts.id', $attemptId)
            ->where('test_purchases.buyer_user_id', $buyerUserId)
            ->select([
                'payment_attempts.id',
                'payment_attempts.status as attempt_status',
                'payment_attempts.expires_at',
                'payment_attempts.paid_at',
                'payment_attempts.failed_at',
                'payment_attempts.expired_at',
                'payment_attempts.cancelled_at',
                'test_purchases.id as purchase_id',
                'test_purchases.test_id',
                'test_purchases.payment_status as purchase_status',
            ])
            ->first();
    }

    public function findForCheckoutSession(?int $attemptId, string $checkoutSessionId): ?object
    {
        if ($attemptId) {
            $attempt = $this->findById($attemptId);

            if ($attempt) {
                return $attempt;
            }
        }

        return $this->findByProviderReference($checkoutSessionId);
    }

    public function findForPaymentIntent(?int $attemptId, string $paymentIntentId): ?object
    {
        if ($attemptId) {
            $attempt = $this->findById($attemptId);

            if ($attempt) {
                return $attempt;
            }
        }

        return $this->findByPaymentIntentReference($paymentIntentId);
    }

    public function markAsSucceeded(int $attemptId): void
    {
        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->update([
                'status' => PaymentAttemptStatus::Succeeded->value,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markAsFailed(
        int $attemptId,
        ?string $failureCode = null,
        ?string $failureMessage = null
    ): void {
        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->update([
                'status' => PaymentAttemptStatus::Failed->value,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'failed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markAsExpired(int $attemptId): void
    {
        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->update([
                'status' => PaymentAttemptStatus::Expired->value,
                'expired_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function hasActivePendingAttemptForPurchase(int $testPurchaseId): bool
    {
        return DB::table('payment_attempts')
            ->where('test_purchase_id', $testPurchaseId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function updateProviderReferencesIfMissing(int $attemptId, ?string $checkoutSessionId = null, ?string $paymentIntentId = null): void
    {
        $updates = [
            'updated_at' => now(),
        ];

        if ($checkoutSessionId) {
            $updates['provider_reference'] = $checkoutSessionId;
        }

        if ($paymentIntentId) {
            $updates['provider_payment_intent_reference'] = $paymentIntentId;
        }

        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->update($updates);
    }

    public function findReusablePendingAttemptForPurchase(
        int $testPurchaseId,
        ?float $sourceAmount = null,
        ?string $sourceCurrency = null,
        ?string $providerCurrency = null,
    ): ?object
    {
        $query = DB::table('payment_attempts')
            ->where('test_purchase_id', $testPurchaseId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->whereNotNull('provider_reference')
            ->whereNotNull('checkout_url')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()->addMinute());
            });

        if ($sourceAmount !== null) {
            $query->where('source_amount', round($sourceAmount, 2));
        }

        if ($sourceCurrency !== null) {
            $query->where('source_currency', strtolower($sourceCurrency));
        }

        if ($providerCurrency !== null) {
            $query->where('currency', strtolower($providerCurrency));
        }

        return $query
            ->latest('id')
            ->first();
    }

    public function expireLocalPendingAttemptsForPurchase(int $testPurchaseId): int
    {
        return DB::table('payment_attempts')
            ->where('test_purchase_id', $testPurchaseId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => PaymentAttemptStatus::Expired->value,
                'expired_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function getPurchaseIdsWithExpiredPendingAttempts(int $limit = 500): array
    {
        return DB::table('payment_attempts')
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->select('test_purchase_id')
            ->distinct()
            ->limit($limit)
            ->pluck('test_purchase_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function markExpiredPendingAttemptsForPurchases(array $purchaseIds): int
    {
        if (empty($purchaseIds)) {
            return 0;
        }

        return DB::table('payment_attempts')
            ->whereIn('test_purchase_id', $purchaseIds)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => PaymentAttemptStatus::Expired->value,
                'expired_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function recordFailureWithoutClosingAttempt(
        int $attemptId,
        ?string $failureCode = null,
        ?string $failureMessage = null
    ): void {
        DB::table('payment_attempts')
            ->where('id', $attemptId)
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->update([
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'failed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}

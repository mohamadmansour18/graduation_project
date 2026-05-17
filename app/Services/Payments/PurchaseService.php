<?php

namespace App\Services\Payments;

use App\DTOs\Payments\CreateCheckoutSessionData;
use App\Enums\Payments\PaymentProvider;
use App\Enums\Payments\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Exceptions\Api\PaymentException;
use App\Repositories\Payments\TestPaymentRepository;
use App\Repositories\Payments\TestPurchaseRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchaseService
{
    public function __construct(
        private readonly TestPaymentRepository $testPaymentRepository,
        private readonly TestPurchaseRepository $testPurchaseRepository,
        private readonly PurchaseMoneyCalculator $moneyCalculator,
        private readonly PaymentManager $paymentManager,
    )
    {}

    public function createCheckoutSessionForTest(int $testId, int $buyerUserId): array
    {
        $test = $this->testPaymentRepository->findTestForPurchase($testId);

        if (! $test) {
            throw PaymentException::testNotFound();
        }

        $this->ensureTestCanBePurchased($test, $buyerUserId);

        $currency = config('payments.default_currency', 'usd');

        $money = $this->moneyCalculator->calculate(
            grossAmount: (float) $test->price,
            currency: $currency,
        );

        $provider = PaymentProvider::from(
            config('payments.default_provider', PaymentProvider::Stripe->value)
        );

        $purchase = $this->testPurchaseRepository->createPendingPurchase([
            'test_id' => $test->id,
            'buyer_user_id' => $buyerUserId,
            'seller_user_id' => $test->creator_user_id,
            'gross_amount' => $money->grossAmount,
            'platform_fee_amount' => $money->platformFeeAmount,
            'seller_net_amount' => $money->sellerNetAmount,
            'currency' => $money->currency,
            'payment_provider' => $provider->value,
        ]);

        if ($purchase->payment_status === PaymentStatus::Paid->value) {
            throw PaymentException::testAlreadyPurchased();
        }

        $purchaseId = $purchase->id;

        try {
            $checkoutSession = $this->paymentManager->driver($provider)->createCheckoutSession(
                new CreateCheckoutSessionData (
                    purchaseId: $purchaseId,
                    testId: $test->id,
                    buyerUserId: $buyerUserId,
                    sellerUserId: $test->creator_user_id,
                    testTitle: $test->title,
                    money: $money,
                    successUrl: config('payments.success_url'),
                    cancelUrl: config('payments.cancel_url'),
                    metadata: [
                        'source' => 'mobile_app',
                        'purchase_type' => 'test',
                    ],
                )
            );

            $this->testPurchaseRepository->updatePaymentReference(
                purchaseId: $purchaseId,
                paymentReference: $checkoutSession->checkoutSessionId,
            );

            return [
                'purchase_id' => $purchaseId,
                'provider' => $checkoutSession->provider,
                'checkout_session_id' => $checkoutSession->checkoutSessionId,
                'checkout_url' => $checkoutSession->checkoutUrl,
                'amount' => [
                    'gross_amount' => $money->grossAmount,
                    'platform_fee_amount' => $money->platformFeeAmount,
                    'seller_net_amount' => $money->sellerNetAmount,
                    'currency' => $money->currency,
                ],
            ];
        } catch (Throwable $exception) {

            $this->testPurchaseRepository->markAsFailed($purchaseId);

            Log::channel('errors')->error('Stripe checkout session creation failed', [
                'purchase_id' => $purchaseId,
                'test_id' => $test->id,
                'buyer_user_id' => $buyerUserId,
                'provider' => $provider->value,
                'message' => $exception->getMessage(),
            ]);

            throw PaymentException::checkoutSessionCreationFailed();
        }
    }

    private function ensureTestCanBePurchased(object $test, int $buyerUserId): void
    {
        if ($test->creator_user_id === $buyerUserId) {
            throw PaymentException::cannotPurchaseOwnTest();
        }

        if ($test->test_type !== TestType::Public->value) {
            throw PaymentException::testIsNotPublic();
        }

        if ($test->review_status !== TestReviewStatus::Approved->value) {
            throw PaymentException::testIsNotApproved();
        }

        if ($test->price === null || (float) $test->price <= 0) {
            throw PaymentException::testIsFree();
        }

        if ($this->testPurchaseRepository->userHasPaidPurchase($test->id, $buyerUserId)) {
            throw PaymentException::testAlreadyPurchased();
        }
    }
}

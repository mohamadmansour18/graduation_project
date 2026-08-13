<?php

namespace App\Http\Controllers\V1\Payments;

use App\Services\Payments\PurchaseService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestPaymentController
{
    use ApiResponse;

    public function __construct(
        private readonly PurchaseService $purchaseService,
    )
    {}

    public function createStripeCheckoutSession(int $testId): JsonResponse
    {
        $checkoutData = $this->purchaseService->createCheckoutSessionForTest(
            testId: $testId,
            buyerUserId: Auth::id(),
        );

        return $this->dataResponse(
            data: $checkoutData,
            title: '! تم إنشاء جلسة الدفع بنجاح'
        );
    }

    public function paymentAttemptStatus(int $attemptId): JsonResponse
    {
        $status = $this->purchaseService->getPaymentAttemptStatus(
            attemptId: $attemptId,
            buyerUserId: (int) Auth::id(),
        );

        return $this->dataResponse(
            data: $status,
            title: '! تم جلب حالة الدفع بنجاح',
        );
    }
}

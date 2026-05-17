<?php

namespace App\Http\Controllers\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeWebhookService $stripeWebhookService,
    )
    {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        $signatureHeader = $request->header('Stripe-Signature');

        $endpointSecret = config('payments.stripe.webhook_secret');

        if (! $endpointSecret) {
            Log::channel('errors')->error('Stripe webhook secret is not configured');

            return response()->json([
                'received' => false,
                'message' => 'Webhook secret is not configured.',
            ], 500);
        }

        try {
            $event = Webhook::constructEvent(
                payload: $payload,
                sigHeader: $signatureHeader,
                secret: $endpointSecret,
            );
        } catch (UnexpectedValueException $exception) {
            Log::channel('errors')->warning('Stripe webhook payload is invalid', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'received' => false,
                'message' => 'Invalid payload.',
            ], 400);
        } catch (SignatureVerificationException $exception) {
            Log::channel('errors')->warning('Stripe webhook signature verification failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'received' => false,
                'message' => 'Invalid signature.',
            ], 400);
        }

        $this->stripeWebhookService->handle($event);

        return response()->json([
            'received' => true,
        ]);
    }
}

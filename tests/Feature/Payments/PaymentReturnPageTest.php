<?php

namespace Tests\Feature\Payments;

use Tests\TestCase;

class PaymentReturnPageTest extends TestCase
{
    public function test_success_return_page_is_available_and_contains_the_payment_attempt_deep_link(): void
    {
        $response = $this->get('/payment/return/success?payment_attempt_id=42');

        $response
            ->assertOk()
            ->assertSee('تم استلام عملية الدفع')
            ->assertSee('nerd://payment/return/success?payment_attempt_id=42', false);
    }

    public function test_cancel_return_page_is_available_and_contains_the_payment_attempt_deep_link(): void
    {
        $response = $this->get('/payment/return/cancel?payment_attempt_id=42');

        $response
            ->assertOk()
            ->assertSee('تم إلغاء عملية الدفع')
            ->assertSee('nerd://payment/return/cancel?payment_attempt_id=42', false);
    }
}

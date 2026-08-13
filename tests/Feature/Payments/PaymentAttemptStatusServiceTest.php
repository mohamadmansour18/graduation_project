<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\Payments\PaymentAttemptStatus;
use App\Exceptions\Api\PaymentException;
use App\Repositories\Payments\PaymentAttemptRepository;
use App\Repositories\Payments\TestPaymentRepository;
use App\Repositories\Payments\TestPurchaseRepository;
use App\Services\Payments\CheckoutMinimumAmountService;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PurchaseMoneyCalculator;
use App\Services\Payments\PurchaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PaymentAttemptStatusServiceTest extends TestCase
{
    private PurchaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('test_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('buyer_user_id');
            $table->unsignedBigInteger('test_id');
            $table->string('payment_status');
        });

        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_purchase_id');
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        DB::table('test_purchases')->insert([
            'id' => 11,
            'buyer_user_id' => 7,
            'test_id' => 91,
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        DB::table('payment_attempts')->insert([
            'id' => 20,
            'test_purchase_id' => 11,
            'status' => PaymentAttemptStatus::Succeeded->value,
            'paid_at' => now(),
        ]);

        $this->service = new PurchaseService(
            Mockery::mock(TestPaymentRepository::class),
            Mockery::mock(TestPurchaseRepository::class),
            Mockery::mock(PurchaseMoneyCalculator::class),
            Mockery::mock(CheckoutMinimumAmountService::class),
            Mockery::mock(PaymentManager::class),
            new PaymentAttemptRepository(),
        );
    }

    public function test_returns_a_paid_attempt_only_to_its_buyer(): void
    {
        $status = $this->service->getPaymentAttemptStatus(20, 7);

        $this->assertSame(20, $status['payment_attempt_id']);
        $this->assertSame(91, $status['test_id']);
        $this->assertSame('paid', $status['status']);
        $this->assertTrue($status['test_access_granted']);
    }

    public function test_does_not_disclose_an_attempt_to_another_buyer(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionCode(404);

        $this->service->getPaymentAttemptStatus(20, 8);
    }
}

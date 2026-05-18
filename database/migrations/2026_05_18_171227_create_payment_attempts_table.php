<?php

use App\Enums\Payments\PaymentAttemptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_purchase_id')->constrained('test_purchases')->cascadeOnDelete();
            $table->string('payment_provider', 50);
            $table->string('provider_reference')->nullable()->unique();
            $table->string('provider_payment_intent_reference')->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);
            $table->enum('status', [
                PaymentAttemptStatus::Pending->value,
                PaymentAttemptStatus::Succeeded->value,
                PaymentAttemptStatus::Failed->value,
                PaymentAttemptStatus::Expired->value,
                PaymentAttemptStatus::Cancelled->value,
            ])->default(PaymentAttemptStatus::Pending->value);

            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['test_purchase_id', 'status']);
            $table->index(['payment_provider', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};

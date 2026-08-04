<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('platform_fee_amount', 12, 2)->default(0);
            $table->decimal('seller_net_amount', 12, 2);
            $table->string('currency', 10)->default('ليرة سورية');
            $table->string('payment_provider')->default('stripe');
            $table->string('payment_reference')->unique()->nullable();
            $table->enum('payment_status' , array_column(PaymentStatus::cases() , 'value'))->default(PaymentStatus::Pending->value);
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'buyer_user_id']);
            $table->index(
                ['seller_user_id', 'payment_status', 'purchased_at', 'id'],
                'test_purchases_seller_status_purchased_id_idx'
            );
            $table->index(
                ['payment_status', 'currency', 'purchased_at', 'id'],
                'test_purchases_status_currency_purchased_id_idx'
            );
            $table->index(
                ['test_id', 'payment_status', 'purchased_at'],
                'test_purchases_test_status_purchased_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_purchases');
    }
};

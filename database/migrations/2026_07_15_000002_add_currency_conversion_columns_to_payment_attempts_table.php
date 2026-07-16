<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->decimal('source_amount', 12, 2)->nullable()->after('currency');
            $table->string('source_currency', 10)->nullable()->after('source_amount');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('source_currency');
            $table->string('exchange_rate_provider', 100)->nullable()->after('exchange_rate');
            $table->timestamp('exchange_rate_fetched_at')->nullable()->after('exchange_rate_provider');
            $table->timestamp('exchange_rate_expires_at')->nullable()->after('exchange_rate_fetched_at');
            $table->boolean('exchange_rate_is_fallback')->default(false)->after('exchange_rate_expires_at');

            $table->index(['source_currency', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropIndex(['source_currency', 'currency']);
            $table->dropColumn([
                'source_amount',
                'source_currency',
                'exchange_rate',
                'exchange_rate_provider',
                'exchange_rate_fetched_at',
                'exchange_rate_expires_at',
                'exchange_rate_is_fallback',
            ]);
        });
    }
};

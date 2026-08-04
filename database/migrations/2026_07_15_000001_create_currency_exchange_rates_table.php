<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('source_currency', 10);
            $table->string('target_currency', 10);
            $table->decimal('rate', 18, 8);
            $table->string('provider', 100);
            $table->date('effective_date')->index();
            $table->timestamp('fetched_at')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_fallback')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_currency', 'target_currency', 'effective_date'] , 'currency_exchange_rates_ste');
            $table->index(['source_currency', 'target_currency', 'provider'] , 'currency_exchange_rates_stp');
            $table->index(
                ['source_currency', 'target_currency', 'effective_date', 'fetched_at', 'id'],
                'currency_rates_pair_effective_fetched_id_idx'
            );
            $table->index(
                ['source_currency', 'target_currency', 'fetched_at', 'id'],
                'currency_rates_pair_fetched_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};

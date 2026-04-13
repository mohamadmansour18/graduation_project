<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_yearly_test_sales_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('purchase_count')->default(0);
            $table->decimal('gross_sales_amount', 12, 2)->default(0);
            $table->decimal('users_profit_amount', 12, 2)->default(0);
            $table->decimal('platform_net_profit_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['year', 'test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_yearly_test_sales_stats');
    }
};

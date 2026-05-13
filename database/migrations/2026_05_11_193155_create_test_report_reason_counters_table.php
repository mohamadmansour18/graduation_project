<?php

use App\Enums\TestReportsReason;
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
        Schema::create('test_report_reason_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('approval_version');
            $table->enum('reason' , array_column(TestReportsReason::cases(), 'value'));
            $table->unsignedInteger('reporters_count')->default(0);

            $table->timestamps();

            $table->unique(['test_id', 'approval_version', 'reason'], 'test_report_reason_counters_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_report_reason_counters');
    }
};

<?php

use App\Enums\TestReviewReportReason;
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
        Schema::create('test_review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_id')->constrained('test_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason' , array_column(TestReviewReportReason::cases() , 'value'));
            $table->text('description')->nullable();
            $table->timestamp('reported_at');

            $table->timestamps();

            $table->unique(
                ['test_review_id', 'user_id', 'reason'],
                'test_review_reports_unique_reason_per_user'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_review_reports');
    }
};

<?php

use App\Enums\TestReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('test_review_round_id')->nullable()->constrained('test_review_rounds')->nullOnDelete();
            $table->enum('from_status', array_column(TestReviewStatus::cases(), 'value'))->nullable();
            $table->enum('to_status', array_column(TestReviewStatus::cases(), 'value'));
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_status_histories');
    }
};

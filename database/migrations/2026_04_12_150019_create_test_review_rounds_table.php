<?php

use App\Enums\Decision;
use App\Enums\TestReviewRoundsTriggerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedTinyInteger('round_no');
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('trigger_type' , array_column(TestReviewRoundsTriggerType::cases(), 'value'))->nullable();
            $table->enum('decision' , array_column(\App\Enums\Decision::cases(), 'value'));
            $table->unsignedTinyInteger('based_on_approval_version')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'round_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_review_rounds');
    }
};

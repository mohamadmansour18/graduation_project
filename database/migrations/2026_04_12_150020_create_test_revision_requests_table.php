<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_round_id')->constrained('test_review_rounds')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->enum('revision_type' , array_column(\App\Enums\RevisionType::cases(), 'value'));
            $table->foreignId('target_question_id')->nullable()->constrained('test_question')->nullOnDelete();
            $table->foreignId('target_option_id')->nullable()->constrained('test_question_options')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('problem_note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_revision_requests');
    }
};

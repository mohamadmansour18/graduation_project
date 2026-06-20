<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_ai_evaluation_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_id')
                ->constrained('test')
                ->cascadeOnDelete();

            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status', [
                'Pending',
                'Processing',
                'Completed',
                'Failed',
            ])->default('Pending');

            $table->string('content_hash', 64);
            $table->unsignedTinyInteger('questions_count');
            $table->json('input_questions_json');

            $table->string('provider')->nullable();
            $table->string('model')->nullable();

            $table->unsignedTinyInteger('score_percentage')->nullable();
            $table->string('correct_questions_label', 20)->nullable();
            $table->string('suspicious_questions_label', 20)->nullable();
            $table->json('issues_json')->nullable();
            $table->json('raw_response_json')->nullable();

            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->unique(['test_id', 'content_hash'], 'test_ai_eval_test_content_unique');
            $table->index(['status', 'created_at']);
            $table->index(['requested_by_user_id', 'created_at'], 'test_ai_eval_requested_by_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_ai_evaluation_requests');
    }
};

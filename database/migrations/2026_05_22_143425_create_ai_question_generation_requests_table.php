<?php

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
        Schema::create('ai_question_generation_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('source_type', ['Images', 'Pdf']);

            $table->enum('status', [
                'Pending',
                'Processing',
                'Completed',
                'Failed',
            ])->default('Pending');

            $table->unsignedTinyInteger('requested_question_count');

            $table->enum('difficulty_level', [
                'Easy',
                'Medium',
                'Hard',
            ]);

            $table->enum('language', [
                'English',
                'Arabic',
                'Mixed',
            ]);

            $table->string('provider')->nullable();
            $table->string('model')->nullable();

            $table->json('generated_questions_json')->nullable();

            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();


            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(
                ['user_id', 'status', 'created_at'],
                'ai_generation_user_status_created_idx'
            );
            $table->index(
                [
                    'user_id',
                    'source_type',
                    'requested_question_count',
                    'difficulty_level',
                    'language',
                    'status',
                    'id',
                ],
                'ai_generation_reusable_signature_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_question_generation_requests');
    }
};

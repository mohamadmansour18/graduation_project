<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_revision_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_round_id')->constrained('test_review_rounds')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('revision_request_id')->constrained('test_revision_requests')->cascadeOnDelete();
            $table->foreignId('target_question_id')->constrained('test_question')->cascadeOnDelete();
            $table->enum('revision_type' , array_column(\App\Enums\RevisionType::cases(), 'value'));
            $table->Text('before_value');
            $table->Text('after_value');
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_revision_change_logs');
    }
};

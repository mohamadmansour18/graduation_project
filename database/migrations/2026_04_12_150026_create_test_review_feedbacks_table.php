<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_review_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_id')->constrained('test_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vote' , array_column(\App\Enums\Vote::cases(), 'value'));
            $table->timestamps();

            $table->unique(['test_review_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_review_feedbacks');
    }
};

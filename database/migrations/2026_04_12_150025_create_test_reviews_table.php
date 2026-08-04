<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text');
            $table->unsignedInteger('helpful_yes_count')->default(0);
            $table->unsignedInteger('helpful_no_count')->default(0);
            $table->timestamps();

            $table->unique(['test_id', 'user_id']);
            $table->index(
                ['test_id', 'rating', 'id'],
                'test_reviews_test_rating_id_idx'
            );
            $table->index(
                ['test_id', 'helpful_yes_count', 'id'],
                'test_reviews_test_helpful_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_reviews');
    }
};

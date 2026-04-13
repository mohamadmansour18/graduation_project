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
            $table->text('review_text')->nullable();
            $table->unsignedInteger('helpful_yes_count')->default(0);
            $table->unsignedInteger('helpful_no_count')->default(0);
            $table->timestamps();

            $table->unique(['test_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_reviews');
    }
};

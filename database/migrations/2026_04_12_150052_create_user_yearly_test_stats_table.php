<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_yearly_test_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_likes_received')->default(0);
            $table->unsignedInteger('total_reviews_received')->default(0);
            $table->unsignedInteger('total_bookmarks_received')->default(0);
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->timestamp('first_published_test_at')->nullable();
            $table->timestamp('last_published_test_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_yearly_test_stats');
    }
};

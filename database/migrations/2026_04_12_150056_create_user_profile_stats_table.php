<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->unsignedInteger('library_materials_count')->default(0);
            $table->unsignedInteger('folders_count')->default(0);
            $table->decimal('average_test_rating', 4, 2)->default(0);
            $table->unsignedInteger('total_test_likes_received')->default(0);
            $table->unsignedInteger('total_test_reviews_received')->default(0);
            $table->unsignedInteger('total_test_bookmarks_received')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profile_stats');
    }
};

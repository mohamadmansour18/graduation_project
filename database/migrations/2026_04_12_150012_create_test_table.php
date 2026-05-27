<?php

use App\Enums\DifficultyLevel;
use App\Enums\Language;
use App\Enums\TargetLevel;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title' ,100);
            $table->text('description' , 255);
            $table->enum('test_type', array_column(TestType::cases(), 'value'));
            $table->enum('difficulty_level' , array_column(DifficultyLevel::cases(), 'value'));
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('pass_mark_percentage')->nullable();
            $table->enum('language' , array_column(Language::cases() , 'value'));
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('target_level' , array_column(TargetLevel::cases(), 'value'));
            $table->enum('review_status', array_column(TestReviewStatus::cases(), 'value'));
            $table->unsignedInteger('current_approval_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_content_updated_at')->nullable();
            $table->unsignedTinyInteger('question_count');
            $table->unsignedTinyInteger('preview_question_count');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('participants_count')->default(0);
            $table->decimal('average_rating', 4, 2)->default(0);
            $table->string('share_slug', 80)->nullable()->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test');
    }
};

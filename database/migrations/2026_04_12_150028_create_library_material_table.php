<?php

use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title' ,150);
            $table->string('description' , 255);
            $table->enum('content_kind', array_column(LibraryMaterialContentKind::cases(), 'value'));
            $table->enum('visibility_type', array_column(VisibilityType::cases(), 'value'));
            $table->enum('target_level' , array_column(TargetLevel::cases(), 'value'));
            $table->enum('review_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'));
            $table->unsignedInteger('current_approval_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->unsignedTinyInteger('asset_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->string('share_slug', 32)->nullable()->unique();
            $table->timestamps();
            $table->index(
                ['visibility_type', 'review_status', 'published_at', 'id'],
                'lm_visibility_review_published_id_idx'
            );
            $table->index(
                ['creator_user_id', 'visibility_type', 'review_status', 'published_at', 'id'],
                'lm_creator_visibility_review_published_id_idx'
            );
            $table->index(
                ['creator_user_id', 'visibility_type', 'created_at', 'id'],
                'lm_creator_visibility_created_id_idx'
            );
            $table->index(
                ['review_status', 'visibility_type', 'created_at', 'id'],
                'lm_review_visibility_created_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material');
    }
};

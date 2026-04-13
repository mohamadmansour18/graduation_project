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
            $table->foreignId('imposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material');
    }
};

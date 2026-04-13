<?php

use App\Enums\LibraryMaterialReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->enum('from_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'));
            $table->enum('to_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'));
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_status_histories');
    }
};

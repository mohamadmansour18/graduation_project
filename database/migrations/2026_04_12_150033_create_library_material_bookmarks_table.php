<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['library_material_id', 'user_id'] , 'lm_bookmark_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_bookmarks');
    }
};

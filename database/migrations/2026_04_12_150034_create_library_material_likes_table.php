<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['library_material_id', 'user_id'] , 'lm_like_user_unique');
            $table->index(
                ['user_id', 'created_at', 'id'],
                'lm_likes_user_created_id_idx'
            );
            $table->index(
                ['library_material_id', 'id'],
                'lm_likes_material_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_likes');
    }
};

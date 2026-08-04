<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['test_id', 'user_id']);
            $table->index(
                ['user_id', 'created_at', 'id'],
                'test_bookmarks_user_created_id_idx'
            );
            $table->index(
                ['test_id', 'id'],
                'test_bookmarks_test_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_bookmarks');
    }
};

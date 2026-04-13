<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_folder_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_folder_id')->constrained('test_folder')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['test_folder_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_folder_bookmarks');
    }
};

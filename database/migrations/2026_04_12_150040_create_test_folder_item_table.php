<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_folder_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_folder_id')->constrained('test_folder')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['test_folder_id', 'test_id']);
            $table->unique(['test_folder_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_folder_item');
    }
};

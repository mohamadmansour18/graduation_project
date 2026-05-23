<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_question_generation_assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_question_generation_id')
                ->constrained('ai_question_generation_requests')
                ->cascadeOnDelete();

            $table->string('storage_disk')->default('public');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256_hash', 64)->nullable();
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamp('deleted_from_storage_at')->nullable();

            $table->timestamps();

            $table->index('ai_question_generation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_question_generation_assets');
    }
};

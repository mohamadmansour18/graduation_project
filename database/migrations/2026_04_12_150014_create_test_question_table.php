<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('question_text');
            $table->string('hint_text' , 255)->nullable();
            $table->boolean('is_preview')->default(false);
            $table->unsignedTinyInteger('options_count');
            $table->timestamps();

            $table->unique(['test_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_question');
    }
};

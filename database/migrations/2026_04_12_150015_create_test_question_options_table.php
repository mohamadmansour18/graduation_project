<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_question_id')->constrained('test_question')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['test_question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_question_options');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_task_subtask', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_task_id')->constrained('study_task')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('position');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['study_task_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_task_subtask');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_task_occurrence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_task_id')->constrained('study_task')->cascadeOnDelete();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->unsignedInteger('duration_second');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_task_occurrence');
    }
};

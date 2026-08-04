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
            $table->index(
                ['study_plan_id', 'occurrence_date', 'scheduled_start_time', 'id'],
                'task_occurrence_plan_date_start_id_idx'
            );
            $table->index(
                ['occurrence_date', 'scheduled_start_time', 'id'],
                'task_occurrence_date_start_id_idx'
            );
            $table->index(
                ['occurrence_date', 'scheduled_end_time', 'id'],
                'task_occurrence_date_end_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_task_occurrence');
    }
};

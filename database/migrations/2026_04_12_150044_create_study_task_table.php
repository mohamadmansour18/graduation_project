<?php

use App\Enums\StudyTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->foreignId('study_plan_subject_id')->constrained('study_plan_subject')->cascadeOnDelete();
            $table->uuid('task_group_uuid')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->unsignedInteger('duration_seconds_per_day');
            $table->timestamp('deadline_at');
            $table->unsignedInteger('reminder_offset_minutes')->nullable();
            $table->enum('priority' , array_column(\App\Enums\Priority::cases(), 'value'));
            $table->enum('status', array_column(\App\Enums\TaskStatus::cases(), 'value'));
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('missed_at')->nullable();
            $table->enum('repeat_pattern', array_column(\App\Enums\RepeatPattern::cases(), 'value'));
            $table->unsignedTinyInteger('repeat_weekday')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();

            $table->index('task_group_uuid');
            $table->index(
                ['study_plan_id', 'task_group_uuid', 'start_date', 'id'],
                'study_task_plan_group_start_id_idx'
            );
            $table->index(
                ['study_plan_id', 'start_date', 'start_time', 'id'],
                'study_task_plan_start_time_id_idx'
            );
            $table->index(
                ['missed_at', 'deadline_at', 'status', 'id'],
                'study_task_missed_deadline_status_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_task');
    }
};

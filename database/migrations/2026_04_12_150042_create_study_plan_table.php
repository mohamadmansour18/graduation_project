<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('emoji', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('daily_study_minutes');
            $table->boolean('is_default')->default(false);
            $table->unsignedTinyInteger('subjects_count')->default(0);
            $table->unsignedInteger('tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);
            $table->unsignedInteger('missed_tasks_count')->default(0);
            $table->unsignedInteger('pending_tasks_count')->default(0);
            $table->timestamps();
            $table->index(
                ['user_id', 'is_default', 'start_date', 'id'],
                'study_plan_user_default_start_id_idx'
            );
            $table->index(
                ['user_id', 'end_date'],
                'study_plan_user_end_idx'
            );
            $table->index(
                ['user_id', 'start_date', 'id'],
                'study_plan_user_start_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan');
    }
};

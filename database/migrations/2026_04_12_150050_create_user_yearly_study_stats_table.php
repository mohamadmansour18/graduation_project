<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_yearly_study_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_tasks_count')->default(0);
            $table->unsignedInteger('todo_tasks_count')->default(0);
            $table->unsignedInteger('in_progress_tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);
            $table->unsignedInteger('missed_tasks_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_yearly_study_stats');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plan_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->foreignId('study_subject_id')->constrained('study_subject')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();

            $table->unique(['study_plan_id', 'study_subject_id']);
            $table->unique(['study_plan_id', 'slot_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_subject');
    }
};

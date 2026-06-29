<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'name' , 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_subject');
    }
};

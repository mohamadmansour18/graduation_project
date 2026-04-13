<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_school_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('school_stage' , array_column(\App\Enums\SchoolStage::cases(), 'value'));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_school_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('task_reminders_enabled')->default(true);
            $table->enum('week_starts_on' , array_column(\App\Enums\WeekStartsOn::cases() , 'value'))->default(\App\Enums\WeekStartsOn::Saturday->value);
            $table->enum('time_format' , array_column(\App\Enums\TimeFormat::cases() , 'value'))->default(\App\Enums\TimeFormat::H12->value);
            $table->enum('theme_mode' , array_column(\App\Enums\ThemeMode::cases() , 'value'))->default(\App\Enums\ThemeMode::LIGHT->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};

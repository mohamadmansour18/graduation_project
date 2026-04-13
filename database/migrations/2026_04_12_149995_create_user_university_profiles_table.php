<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_university_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('university_name' , array_column(App\Enums\UniversityName::cases(), 'value'));
            $table->unsignedTinyInteger('university_year');
            $table->enum('department' , array_column(App\Enums\UniversityDepartment::cases(), 'value'));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_university_profiles');
    }
};

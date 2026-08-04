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
            $table->unsignedTinyInteger('university_year')->nullable();
            $table->enum('department' , array_column(App\Enums\UniversityDepartment::cases(), 'value'));
            $table->timestamps();
            $table->index(
                ['university_name', 'user_id'],
                'university_profiles_name_user_idx'
            );
            $table->index(
                ['department', 'user_id'],
                'university_profiles_department_user_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_university_profiles');
    }
};

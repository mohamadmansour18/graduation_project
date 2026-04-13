<?php

use App\Enums\DiscoverySource;
use App\Enums\EducationLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_onboarding_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('discovery_source' , array_column(DiscoverySource::cases(), 'value'))->nullable();
            $table->enum('education_level' , array_column(EducationLevel::cases(), 'value'))->nullable();
            $table->unsignedTinyInteger('last_completed_step')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_profiles');
    }
};

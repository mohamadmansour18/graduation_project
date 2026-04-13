<?php

use App\Enums\Governorate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('avatar_disk')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('cover_disk')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('profile_slug' , 180)->unique();
            $table->enum('governorate' , array_column(Governorate::cases() , 'value'));
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profile');
    }
};

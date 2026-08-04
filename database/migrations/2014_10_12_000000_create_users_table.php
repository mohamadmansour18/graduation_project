<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('name' , 150);
            $table->string('email' , 255)->unique();
            $table->string('password' ,255);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->enum('gender' , array_column(Gender::cases(), 'value'));
            $table->boolean('is_academically_verified')->default(false);
            $table->timestamp('academically_verified_at')->nullable();
            $table->unsignedTinyInteger('academic_verification_cancel_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(
                ['role_id', 'deleted_at', 'name', 'id'],
                'users_role_deleted_name_id_idx'
            );
            $table->index(
                ['role_id', 'deleted_at', 'created_at', 'id'],
                'users_role_deleted_created_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

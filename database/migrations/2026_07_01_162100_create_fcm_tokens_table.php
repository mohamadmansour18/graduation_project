<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token', 512)->unique();

            $table->string('platform', 20);
            $table->string('firebase_project', 20);

            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'platform']);
            $table->index(['user_id', 'firebase_project']);
            $table->index(['revoked_at']);
            $table->index(
                ['user_id', 'revoked_at', 'firebase_project'],
                'fcm_tokens_user_revoked_project_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};

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
        Schema::create('scheduled_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('delivery_key')->unique();

            $table->string('notification_type', 100);

            $table->dateTime('deliver_at');

            $table->timestamp('dispatched_at')->nullable();

            $table->json('context')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'notification_type'] , 'scheduled_notification_user_type');
            $table->index(['deliver_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notification_deliveries');
    }
};

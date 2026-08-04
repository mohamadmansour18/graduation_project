<?php

use App\Enums\BanType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('imposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('ban_type', array_column(BanType::cases(), 'value'));
            $table->text('reason');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('lifted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();
            $table->index(
                ['user_id', 'lifted_at', 'starts_at', 'ends_at', 'id'],
                'user_bans_user_active_period_id_idx'
            );
            $table->index(
                ['lifted_at', 'starts_at', 'ban_type', 'ends_at', 'created_at'],
                'user_bans_active_type_period_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};

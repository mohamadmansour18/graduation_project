<?php

use App\Enums\PurposeOTP;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('purpose' , array_column(PurposeOTP::cases(), 'value'));
            $table->string('code_hash' , 255);
            $table->string('send_to_email' , 255);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(
                ['user_id', 'purpose', 'consumed_at', 'revoked_at', 'id'],
                'auth_otp_user_purpose_active_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_otp_codes');
    }
};

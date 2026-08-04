<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_academic_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status' , array_column(\App\Enums\Status::cases(), 'value'))->default(\App\Enums\Status::PENDING->value);
            $table->timestamp('submitted_at');
            $table->boolean('show_certificate_publicly')->default(false);
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(
                ['user_id', 'status', 'reviewed_at', 'id'],
                'academic_requests_user_status_reviewed_id_idx'
            );
            $table->index(
                ['status', 'submitted_at', 'id'],
                'academic_requests_status_submitted_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_academic_verification_requests');
    }
};

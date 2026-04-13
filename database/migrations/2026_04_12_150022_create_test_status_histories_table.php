<?php

use App\Enums\TestReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->enum('from_status', array_column(TestReviewStatus::cases(), 'value'));
            $table->enum('to_status', array_column(TestReviewStatus::cases(), 'value'))->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->cascadeOnDelete()->nul;
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_status_histories');
    }
};

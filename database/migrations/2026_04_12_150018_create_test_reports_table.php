<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reason' , array_column(App\Enums\TestReportsReason::cases(), 'value'));
            $table->string('description' , 255)->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_reports');
    }
};

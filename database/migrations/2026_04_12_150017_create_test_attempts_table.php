<?php

use App\Enums\TestAttemptsMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('mode' , array_column(TestAttemptsMode::cases() , 'value') );
            $table->timestamps();

            $table->unique(['test_id' , 'user_id' , 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};

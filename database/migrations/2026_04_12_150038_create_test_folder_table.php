<?php

use App\Enums\TestType;
use App\Enums\VisibilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_folder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name' , 100);
            $table->string('color_code', 20);
            $table->enum('visibility_type', array_column(VisibilityType::cases(), 'value'));
            $table->enum('contained_test_type', array_column(TestType::cases(), 'value'));
            $table->unsignedTinyInteger('tests_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_folder');
    }
};

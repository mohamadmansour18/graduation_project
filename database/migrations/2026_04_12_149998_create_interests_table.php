<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interest_category_id')->constrained('interest_categories')->cascadeOnDelete();
            $table->string('name' , 255)->unique();
            $table->string('storage_disk' , 50)->default('public');
            $table->string('icon_svg')->nullable();
            $table->string('color', 7)->default('#5583FF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};

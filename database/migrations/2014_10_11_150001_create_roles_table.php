<?php

use App\Enums\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->enum('name' , array_column(SystemRole::cases() , 'value'));
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

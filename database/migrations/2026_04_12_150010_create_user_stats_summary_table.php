<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_completed_mobile_users')->default(0);
            $table->unsignedInteger('male_completed_mobile_users')->default(0);
            $table->unsignedInteger('female_completed_mobile_users')->default(0);
            $table->timestamps();

            $table->unique(['year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_summary');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_yearly_test_publish_month_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year', 'month_no'] , 'user_yearly_test_publish_month_stats_unique' );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_yearly_test_publish_month_stats');
    }
};

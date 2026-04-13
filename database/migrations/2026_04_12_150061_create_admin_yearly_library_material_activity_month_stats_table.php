<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_yearly_library_material_activity_month_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('published_materials_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
            $table->unique(['year', 'month_no'] , 'admin_yearly_library_material_activity_month_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_yearly_library_material_activity_month_stats');
    }
};

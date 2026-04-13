<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('approval_version');
            $table->enum('reason' , array_column(\App\Enums\LibraryReportReason::cases() , 'value'));
            $table->string('description');
            $table->timestamp('reported_at');
            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_reports');
    }
};

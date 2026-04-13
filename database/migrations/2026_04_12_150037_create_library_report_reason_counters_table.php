<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_report_reason_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->unsignedInteger('approval_version');
            $table->enum('reason' , array_column(\App\Enums\LibraryReportReason::cases(), 'value'));
            $table->unsignedInteger('reporters_count')->default(0);
            $table->timestamps();

            $table->unique(['library_material_id', 'approval_version', 'reason'] , 'lm_report_approval_reason_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_report_reason_counters');
    }
};

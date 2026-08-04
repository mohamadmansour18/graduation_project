<?php

use App\Enums\TestReportsReason;
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
            $table->unsignedInteger('approval_version');
            $table->enum('reason' , array_column(TestReportsReason::cases(), 'value'));
            $table->string('description' , 255)->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->unique(['test_id', 'user_id', 'reason', 'approval_version'], 'test_reports_unique_per_version');
            $table->index(
                ['test_id', 'approval_version', 'reported_at', 'id'],
                'test_reports_test_version_reported_id_idx'
            );
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('test_reports');
    }
};

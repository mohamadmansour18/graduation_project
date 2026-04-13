<?php

use App\Enums\LibraryDecision;
use App\Enums\LibraryTriggerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->unsignedInteger('round_no');
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('trigger_type' , array_column(LibraryTriggerType::cases(), 'value'));
            $table->enum('decision' , array_column(LibraryDecision::cases(), 'value'))->default(LibraryDecision::Pending->value);
            $table->unsignedInteger('based_on_approval_version')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['library_material_id', 'round_no'] , 'lm_round_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_review_rounds');
    }
};

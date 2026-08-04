<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_interest_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained('interests')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();

            $table->unique(['library_material_id', 'interest_id'] , 'lm_interest_unique');
            $table->unique(['library_material_id', 'slot_no'] , 'lm_slot_no_unique');
            $table->index(
                ['interest_id', 'library_material_id'],
                'lm_interest_interest_material_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_interest_selections');
    }
};

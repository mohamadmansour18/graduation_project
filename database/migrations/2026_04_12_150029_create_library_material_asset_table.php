<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->enum('asset_type' , array_column(\App\Enums\Asset_type::cases() , 'value'));
            $table->string('storage_disk' , 50)->default('public');
            $table->string('storage_path' , 500);
            $table->string('original_name' , 255);
            $table->string('mime_type' , 100);
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
            $table->unique(
                ['library_material_id', 'position'],
                'lm_asset_material_position_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_asset');
    }
};

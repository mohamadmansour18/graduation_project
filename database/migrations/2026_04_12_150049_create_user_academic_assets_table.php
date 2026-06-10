<?php

use App\Enums\AcademicAssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_academic_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->constrained('user_academic_verification_requests')->cascadeOnDelete();
            $table->enum('asset_type' , array_column(AcademicAssetType::cases() , 'value'));
            $table->string('storage_disk' , 50)->default('local');
            $table->string('storage_path' , 500);
            $table->string('original_name' , 255);
            $table->string('mime_type' , 100);
            $table->timestamps();

            $table->unique(['verification_request_id', 'asset_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_academic_verification_assets');
    }
};

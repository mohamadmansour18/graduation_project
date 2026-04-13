<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats_by_discovery_source', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->enum('discovery_source' , array_column(App\Enums\DiscoverySource::cases() , 'value'));
            $table->unsignedInteger('completed_mobile_users_count')->default(0);
            $table->timestamps();

            $table->unique(['year', 'discovery_source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_by_discovery_source');
    }
};

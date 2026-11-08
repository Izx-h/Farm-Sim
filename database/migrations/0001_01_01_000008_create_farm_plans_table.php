<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_plans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('farm_id');
            $table->string('crop_id');
            $table->string('season_name');
            $table->date('target_planting_date');
            $table->date('estimated_harvest_date');
            $table->float('planted_area_acres');
            $table->string('status')->default('Active');   // Draft, Active, Completed, Abandoned
            $table->float('projected_yield_tons');
            $table->float('projected_revenue');
            $table->float('estimated_total_cost');
            $table->float('projected_net_margin');
            $table->text('notes')->nullable();
            $table->string('variety')->default('');
            $table->timestamps();

            $table->foreign('farm_id')->references('id')->on('farms')->onDelete('cascade');
            $table->foreign('crop_id')->references('id')->on('crops')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_plans');
    }
};

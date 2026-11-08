<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('scientific_name');
            $table->string('category');                 // Cereal, Legume, Tuber, Vegetable, Cash Crop, Fruit, Oilseed, Fodder
            $table->float('min_ph');
            $table->float('max_ph');
            $table->float('optimal_ph_min');
            $table->float('optimal_ph_max');
            $table->float('min_elevation_m');
            $table->float('max_elevation_m');
            $table->float('min_temp_c');
            $table->float('max_temp_c');
            $table->float('optimal_temp_c');
            $table->float('min_rainfall_mm');
            $table->float('max_rainfall_mm');
            $table->integer('maturity_days');
            $table->string('drought_tolerance');        // Low, Moderate, High, Very High
            $table->string('flood_tolerance');          // Low, Moderate, High
            $table->integer('water_requirement_mm');
            $table->float('average_yield_tons_per_acre');
            $table->float('market_price_per_kg');
            $table->float('seed_cost_per_acre');
            $table->float('fertilizer_cost_per_acre');
            $table->float('labor_cost_per_acre');
            $table->integer('commercial_value_rating');
            $table->integer('food_security_rating');
            $table->string('spacing_recommendation');
            $table->float('planting_depth_cm');
            $table->text('description');

            $table->string('variety')->default('');
            $table->string('planting_seasons')->default('');
            $table->string('seed_rate')->default('');
            $table->text('nutrient_requirement')->nullable();
            $table->text('major_pests')->nullable();
            $table->text('harvest_note')->nullable();
            $table->float('salinity_tolerance_ec_ds_m')->default(2.5);
            $table->string('water_requirement_note')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};

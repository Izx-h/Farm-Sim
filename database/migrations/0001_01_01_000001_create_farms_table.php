<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('name');
            $table->string('owner');
            $table->string('location_name');
            $table->float('latitude');
            $table->float('longitude');
            $table->float('elevation_m');
            $table->float('slope_deg');
            $table->string('soil_type');
            $table->float('soil_ph');
            $table->string('nitrogen_level');      // Low, Medium, High
            $table->string('phosphorus_level');
            $table->string('potassium_level');
            $table->float('organic_matter_pct');
            $table->string('drainage_class');      // Well-drained, Moderate, Poor, Excessively drained
            $table->string('water_source');        // Rainfed, Borehole/Well, River/Stream, Drip Irrigation
            $table->float('area_acres');
            $table->string('farming_goal');        // Subsistence, Commercial, Mixed

            $table->float('groundwater_depth_m')->nullable();
            $table->float('groundwater_yield_m3_hr')->nullable();
            $table->float('groundwater_ec_ds_m')->nullable();
            $table->float('groundwater_tds_mg_l')->nullable();
            $table->string('groundwater_salinity_class')->nullable();
            $table->string('groundwater_irrigation_suitability')->nullable();
            $table->string('aquifer_basin')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groundwater_stations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('station_code');
            $table->string('name');
            $table->string('county');
            $table->string('sub_county');
            $table->string('locality');
            $table->float('latitude');
            $table->float('longitude');
            $table->string('aquifer_name');
            $table->string('basin_name');
            $table->float('depth_m');
            $table->float('water_table_m');
            $table->float('yield_m3_hr');
            $table->float('ph');
            $table->float('ec_ds_m');
            $table->float('tds_mg_l');
            $table->float('sar');
            $table->float('fluoride_mg_l');
            $table->float('chloride_mg_l');
            $table->float('boron_mg_l');
            $table->string('salinity_category');
            $table->string('irrigation_suitability');
            $table->string('verified_source');
            $table->string('confidence_level');
            $table->date('last_tested_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groundwater_stations');
    }
};

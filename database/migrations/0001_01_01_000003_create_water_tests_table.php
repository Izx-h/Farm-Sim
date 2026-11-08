<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_tests', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('farm_id');
            $table->date('test_date');
            $table->string('laboratory');
            $table->string('source_type');
            $table->float('depth_m')->nullable();
            $table->float('static_water_level_m')->nullable();
            $table->float('yield_m3_hr')->nullable();
            $table->float('ph')->nullable();
            $table->float('ec_ds_m')->nullable();
            $table->float('tds_mg_l')->nullable();
            $table->float('sar')->nullable();
            $table->float('fluoride_mg_l')->nullable();
            $table->string('salinity_category')->nullable();
            $table->string('irrigation_suitability')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('farm_id')->references('id')->on('farms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_tests');
    }
};

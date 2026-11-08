<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soil_tests', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('farm_id');
            $table->date('test_date');
            $table->string('laboratory');
            $table->float('ph')->nullable();
            $table->float('n_ppm')->nullable();
            $table->float('p_ppm')->nullable();
            $table->float('k_ppm')->nullable();
            $table->float('ec_ds_m')->nullable();
            $table->float('organic_carbon_pct')->nullable();
            $table->text('recommendation_summary')->nullable();
            $table->timestamps();

            $table->foreign('farm_id')->references('id')->on('farms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soil_tests');
    }
};

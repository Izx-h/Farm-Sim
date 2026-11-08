<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('crop_name');
            $table->string('market_name');
            $table->string('region');
            $table->string('unit');                     // 50kg bag, 90kg bag, kg, crate
            $table->float('wholesale_price_ksh');
            $table->float('retail_price_ksh');
            $table->string('price_trend');              // Rising, Stable, Falling
            $table->string('demand_level');             // High, Moderate, Low
            $table->date('last_updated');
            $table->string('source')->default('reference');
            $table->string('currency')->default('KSh');
            $table->integer('is_live')->default(0);
            $table->string('source_url')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};

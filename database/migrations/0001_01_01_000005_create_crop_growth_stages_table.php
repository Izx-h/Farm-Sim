<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_growth_stages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('crop_id');
            $table->string('stage_name');
            $table->integer('stage_order');
            $table->integer('start_day');
            $table->integer('end_day');
            $table->text('water_need_description');
            $table->text('key_activities');
            $table->text('scout_priorities');

            $table->foreign('crop_id')->references('id')->on('crops')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_growth_stages');
    }
};

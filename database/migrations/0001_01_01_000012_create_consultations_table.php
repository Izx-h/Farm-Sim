<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('farm_id');
            $table->string('specialist_id');
            $table->string('farmer_name');
            $table->string('inquiry_type');             // Soil Test Review, Pest/Disease Diagnosis, Crop Planning, Irrigation Design
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('Open');  // Open, In Review, Resolved
            $table->text('specialist_response')->nullable();
            $table->timestamps();

            $table->foreign('farm_id')->references('id')->on('farms')->onDelete('cascade');
            $table->foreign('specialist_id')->references('id')->on('specialists')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pest_diseases', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('type');                     // Pest, Fungal Disease, Bacterial Disease, Viral Disease, Weed
            $table->text('affected_crops');
            $table->text('symptoms');
            $table->text('favorable_conditions');
            $table->string('severity');                 // Low, Moderate, High, Severe
            $table->text('prevention_measures');
            $table->text('chemical_control');
            $table->text('biological_control');
            $table->string('image_placeholder')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pest_diseases');
    }
};

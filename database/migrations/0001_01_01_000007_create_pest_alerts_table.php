<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pest_alerts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('region');
            $table->string('pest_disease_id');
            $table->string('alert_level');              // Watch, Warning, Emergency
            $table->text('summary');
            $table->text('affected_crops');
            $table->text('action_required');
            $table->date('reported_date');
            $table->string('county')->default('');
            $table->string('source_type')->default('surveillance');

            $table->foreign('pest_disease_id')->references('id')->on('pest_diseases');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pest_alerts');
    }
};

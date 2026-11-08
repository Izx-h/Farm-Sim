<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialists', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('role');
            $table->string('organization');
            $table->text('specialization');
            $table->integer('experience_years');
            $table->string('email');
            $table->string('phone');
            $table->string('location');
            $table->float('rating');
            $table->integer('consultations_count');
            $table->integer('verified')->default(1);
            $table->integer('available')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialists');
    }
};

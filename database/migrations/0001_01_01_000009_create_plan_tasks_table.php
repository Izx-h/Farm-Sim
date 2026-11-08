<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_tasks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('plan_id');
            $table->string('title');
            $table->string('category');                 // Land Prep, Planting, Fertilization, Weeding, Irrigation, Pest Control, Scouting, Harvesting
            $table->string('growth_stage');
            $table->integer('due_day_offset');          // Days from planting date
            $table->string('status')->default('Pending'); // Pending, In Progress, Completed, Skipped
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreign('plan_id')->references('id')->on('farm_plans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tasks');
    }
};

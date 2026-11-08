<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    use UsesStringId;

    // Static agronomic reference catalogue — no created_at/updated_at columns.
    public $timestamps = false;

    protected $fillable = [
        'name', 'scientific_name', 'category',
        'min_ph', 'max_ph', 'optimal_ph_min', 'optimal_ph_max',
        'min_elevation_m', 'max_elevation_m', 'min_temp_c', 'max_temp_c', 'optimal_temp_c',
        'min_rainfall_mm', 'max_rainfall_mm', 'maturity_days',
        'drought_tolerance', 'flood_tolerance', 'water_requirement_mm',
        'average_yield_tons_per_acre', 'market_price_per_kg',
        'seed_cost_per_acre', 'fertilizer_cost_per_acre', 'labor_cost_per_acre',
        'commercial_value_rating', 'food_security_rating',
        'spacing_recommendation', 'planting_depth_cm', 'description',
        'variety', 'planting_seasons', 'seed_rate', 'nutrient_requirement',
        'major_pests', 'harvest_note', 'salinity_tolerance_ec_ds_m', 'water_requirement_note',
    ];

    protected $casts = [
        'min_ph' => 'float',
        'max_ph' => 'float',
        'optimal_ph_min' => 'float',
        'optimal_ph_max' => 'float',
        'min_elevation_m' => 'float',
        'max_elevation_m' => 'float',
        'min_temp_c' => 'float',
        'max_temp_c' => 'float',
        'optimal_temp_c' => 'float',
        'min_rainfall_mm' => 'float',
        'max_rainfall_mm' => 'float',
        'maturity_days' => 'integer',
        'water_requirement_mm' => 'integer',
        'average_yield_tons_per_acre' => 'float',
        'market_price_per_kg' => 'float',
        'seed_cost_per_acre' => 'float',
        'fertilizer_cost_per_acre' => 'float',
        'labor_cost_per_acre' => 'float',
        'commercial_value_rating' => 'integer',
        'food_security_rating' => 'integer',
        'planting_depth_cm' => 'float',
        'salinity_tolerance_ec_ds_m' => 'float',
    ];

    public function growthStages()
    {
        return $this->hasMany(CropGrowthStage::class)->orderBy('stage_order');
    }
}

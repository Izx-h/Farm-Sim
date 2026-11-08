<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    use UsesStringId;

    protected $fillable = [
        'user_id', 'name', 'owner', 'location_name', 'latitude', 'longitude',
        'elevation_m', 'slope_deg', 'soil_type', 'soil_ph',
        'nitrogen_level', 'phosphorus_level', 'potassium_level', 'organic_matter_pct',
        'drainage_class', 'water_source', 'area_acres', 'farming_goal',
        'groundwater_depth_m', 'groundwater_yield_m3_hr', 'groundwater_ec_ds_m',
        'groundwater_tds_mg_l', 'groundwater_salinity_class',
        'groundwater_irrigation_suitability', 'aquifer_basin', 'notes',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'elevation_m' => 'float',
        'slope_deg' => 'float',
        'soil_ph' => 'float',
        'organic_matter_pct' => 'float',
        'area_acres' => 'float',
        'groundwater_depth_m' => 'float',
        'groundwater_yield_m3_hr' => 'float',
        'groundwater_ec_ds_m' => 'float',
        'groundwater_tds_mg_l' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function soilTests()
    {
        return $this->hasMany(SoilTest::class);
    }

    public function waterTests()
    {
        return $this->hasMany(WaterTest::class);
    }

    public function plans()
    {
        return $this->hasMany(FarmPlan::class);
    }
}

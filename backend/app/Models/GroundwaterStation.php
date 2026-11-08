<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class GroundwaterStation extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'station_code', 'name', 'county', 'sub_county', 'locality',
        'latitude', 'longitude', 'aquifer_name', 'basin_name', 'depth_m',
        'water_table_m', 'yield_m3_hr', 'ph', 'ec_ds_m', 'tds_mg_l', 'sar',
        'fluoride_mg_l', 'chloride_mg_l', 'boron_mg_l', 'salinity_category',
        'irrigation_suitability', 'verified_source', 'confidence_level',
        'last_tested_date',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'depth_m' => 'float',
        'water_table_m' => 'float',
        'yield_m3_hr' => 'float',
        'ph' => 'float',
        'ec_ds_m' => 'float',
        'tds_mg_l' => 'float',
        'sar' => 'float',
        'fluoride_mg_l' => 'float',
        'chloride_mg_l' => 'float',
        'boron_mg_l' => 'float',
        'last_tested_date' => 'date',
    ];
}

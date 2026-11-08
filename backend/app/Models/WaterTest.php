<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class WaterTest extends Model
{
    use UsesStringId;

    protected $fillable = [
        'farm_id', 'test_date', 'laboratory', 'source_type', 'depth_m',
        'static_water_level_m', 'yield_m3_hr', 'ph', 'ec_ds_m', 'tds_mg_l',
        'sar', 'fluoride_mg_l', 'salinity_category', 'irrigation_suitability', 'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
        'depth_m' => 'float',
        'static_water_level_m' => 'float',
        'yield_m3_hr' => 'float',
        'ph' => 'float',
        'ec_ds_m' => 'float',
        'tds_mg_l' => 'float',
        'sar' => 'float',
        'fluoride_mg_l' => 'float',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
}

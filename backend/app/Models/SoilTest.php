<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class SoilTest extends Model
{
    use UsesStringId;

    protected $fillable = [
        'farm_id', 'test_date', 'laboratory', 'ph', 'n_ppm', 'p_ppm', 'k_ppm',
        'ec_ds_m', 'organic_carbon_pct', 'recommendation_summary',
    ];

    protected $casts = [
        'test_date' => 'date',
        'ph' => 'float',
        'n_ppm' => 'float',
        'p_ppm' => 'float',
        'k_ppm' => 'float',
        'ec_ds_m' => 'float',
        'organic_carbon_pct' => 'float',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class FarmPlan extends Model
{
    use UsesStringId;

    protected $fillable = [
        'farm_id', 'crop_id', 'season_name', 'target_planting_date',
        'estimated_harvest_date', 'planted_area_acres', 'status',
        'projected_yield_tons', 'projected_revenue', 'estimated_total_cost',
        'projected_net_margin', 'notes', 'variety',
    ];

    protected $casts = [
        'target_planting_date' => 'date',
        'estimated_harvest_date' => 'date',
        'planted_area_acres' => 'float',
        'projected_yield_tons' => 'float',
        'projected_revenue' => 'float',
        'estimated_total_cost' => 'float',
        'projected_net_margin' => 'float',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }

    public function tasks()
    {
        return $this->hasMany(PlanTask::class)->orderBy('due_day_offset');
    }
}

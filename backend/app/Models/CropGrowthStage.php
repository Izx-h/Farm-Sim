<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class CropGrowthStage extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'crop_id', 'stage_name', 'stage_order', 'start_day', 'end_day',
        'water_need_description', 'key_activities', 'scout_priorities',
    ];

    protected $casts = [
        'stage_order' => 'integer',
        'start_day' => 'integer',
        'end_day' => 'integer',
    ];

    public function crop()
    {
        return $this->belongsTo(Crop::class);
    }
}

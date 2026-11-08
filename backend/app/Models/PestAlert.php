<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class PestAlert extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'region', 'pest_disease_id', 'alert_level', 'summary', 'affected_crops',
        'action_required', 'reported_date', 'county', 'source_type',
    ];

    protected $casts = [
        'reported_date' => 'date',
    ];

    public function pestDisease()
    {
        return $this->belongsTo(PestDisease::class);
    }
}

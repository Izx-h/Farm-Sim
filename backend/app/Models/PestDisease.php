<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class PestDisease extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'name', 'type', 'affected_crops', 'symptoms', 'favorable_conditions',
        'severity', 'prevention_measures', 'chemical_control', 'biological_control',
        'image_placeholder',
    ];

    public function alerts()
    {
        return $this->hasMany(PestAlert::class);
    }
}

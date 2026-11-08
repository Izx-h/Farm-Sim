<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use UsesStringId;

    protected $fillable = [
        'farm_id', 'specialist_id', 'farmer_name', 'inquiry_type',
        'subject', 'message', 'status', 'specialist_response',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }
}

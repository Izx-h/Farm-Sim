<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'name', 'role', 'organization', 'specialization', 'experience_years',
        'email', 'phone', 'location', 'rating', 'consultations_count',
        'verified', 'available',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'rating' => 'float',
        'consultations_count' => 'integer',
        'verified' => 'integer',
        'available' => 'integer',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
    use UsesStringId;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'onboarding_completed',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'onboarding_completed' => 'integer',
    ];

    public function farms()
    {
        return $this->hasMany(Farm::class);
    }
}

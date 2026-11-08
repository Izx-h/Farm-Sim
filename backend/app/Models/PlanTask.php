<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class PlanTask extends Model
{
    use UsesStringId;

    // `completed_at` (cast below) tracks completion; the table has no
    // created_at/updated_at columns, matching the original schema.
    public $timestamps = false;

    protected $fillable = [
        'plan_id', 'title', 'category', 'growth_stage', 'due_day_offset',
        'status', 'notes', 'completed_at',
    ];

    protected $casts = [
        'due_day_offset' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(FarmPlan::class);
    }
}

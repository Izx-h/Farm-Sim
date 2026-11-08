<?php

namespace App\Models;

use App\Models\Concerns\UsesStringId;
use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    use UsesStringId;

    public $timestamps = false;

    protected $fillable = [
        'crop_name', 'market_name', 'region', 'unit',
        'wholesale_price_ksh', 'retail_price_ksh', 'price_trend', 'demand_level',
        'last_updated', 'source', 'currency', 'is_live', 'source_url',
    ];

    protected $casts = [
        'wholesale_price_ksh' => 'float',
        'retail_price_ksh' => 'float',
        'last_updated' => 'date',
        'is_live' => 'integer',
    ];
}

<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Services\DecisionEngine;
use Illuminate\Http\Request;

class EconomicsController extends Controller
{
    public function __construct(private DecisionEngine $engine)
    {
    }

    public function simulate(Request $request)
    {
        $crop = Crop::find($request->input('crop_id'));
        if (! $crop) {
            return response()->json(['error' => 'Crop not found'], 404);
        }

        $simulation = $this->engine->calculateCropEconomics($crop, $request->input('acres', 1), [
            'currentMarketPrice' => $request->input('custom_market_price'),
        ]);

        return response()->json($simulation);
    }
}

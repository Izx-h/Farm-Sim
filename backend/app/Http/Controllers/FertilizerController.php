<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\SoilTest;
use App\Services\DecisionEngine;
use Illuminate\Http\Request;

class FertilizerController extends Controller
{
    public function __construct(private DecisionEngine $engine)
    {
    }

    public function show(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $cropId = $request->query('crop_id');
        $crop = $cropId ? Crop::find($cropId) : null;
        if ($cropId && ! $crop) {
            return response()->json(['error' => 'Crop not found'], 404);
        }

        $soilTest = SoilTest::where('farm_id', $farm->id)->orderByDesc('test_date')->first();

        if ($crop) {
            $results = [$this->engine->calculateFertilizerRecommendation($crop, $soilTest, $farm->area_acres)];
        } else {
            $results = Crop::all()
                ->map(fn ($c) => $this->engine->calculateFertilizerRecommendation($c, $soilTest, $farm->area_acres))
                ->values();
        }

        return response()->json([
            'farmId' => $farm->id,
            'soilTest' => $soilTest,
            'recommendations' => $results,
        ]);
    }
}

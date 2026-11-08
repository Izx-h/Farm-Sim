<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farm;
use App\Services\DecisionEngine;
use Illuminate\Http\Request;

class CropController extends Controller
{
    public function __construct(private DecisionEngine $engine)
    {
    }

    public function index()
    {
        return response()->json(Crop::orderBy('name')->get());
    }

    public function show(string $id)
    {
        $crop = Crop::find($id);
        if (! $crop) {
            return response()->json(['error' => 'Crop not found'], 404);
        }

        return response()->json([
            ...$crop->toArray(),
            'stages' => $crop->growthStages()->orderBy('stage_order')->get(),
        ]);
    }

    public function suitability(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $evaluations = Crop::all()
            ->map(function ($crop) use ($farm) {
                $evaluation = $this->engine->evaluateCropSuitability($farm, $crop);
                $economics = $this->engine->calculateCropEconomics($crop, $farm->area_acres);

                return $evaluation + [
                    'cropDetails' => $crop,
                    'economics' => $economics,
                ];
            })
            ->sortByDesc('suitabilityScore')
            ->values();

        $highlySuitable = $evaluations->filter(fn ($e) => $e['suitabilityScore'] >= 80);
        $moderatelySuitable = $evaluations->filter(fn ($e) => $e['suitabilityScore'] >= 65 && $e['suitabilityScore'] < 80);
        $lowOrRisky = $evaluations->filter(fn ($e) => $e['suitabilityScore'] < 65);

        return response()->json([
            'farm' => $farm,
            'totalEvaluated' => $evaluations->count(),
            'recommendationSummary' => [
                'topPick' => $evaluations->first(),
                'highlySuitableCount' => $highlySuitable->count(),
                'moderatelySuitableCount' => $moderatelySuitable->count(),
                'riskyCount' => $lowOrRisky->count(),
            ],
            'evaluations' => $evaluations,
        ]);
    }
}

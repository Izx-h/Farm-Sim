<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function __construct(private WeatherService $weather)
    {
    }

    public function show(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $weather = $this->weather->getWeather($farm->latitude, $farm->longitude);

        if (($weather['dataStatus'] ?? 'unavailable') === 'unavailable') {
            return response()->json([
                'farmLocation' => $farm->location_name,
                'elevation' => $farm->elevation_m,
                'dataStatus' => 'unavailable',
                'error' => $weather['error'] ?? 'Weather service unreachable',
                'advisory' => null,
            ]);
        }

        $advisory = $this->weather->buildPlantingAdvisory($weather, $farm);

        return response()->json([
            'farmLocation' => $farm->location_name,
            'elevation' => $farm->elevation_m,
            'dataStatus' => $weather['dataStatus'] ?? 'ok',
            'retrievedAt' => $weather['retrievedAt'] ?? null,
            'source' => $weather['source'] ?? null,
            'sourceUrl' => $weather['sourceUrl'] ?? null,
            'forecastHorizon' => $weather['forecastHorizon'] ?? null,
            'current' => $weather['current'] ?? null,
            'forecastDays' => $weather['forecastDays'] ?? [],
            'units' => $weather['units'] ?? null,
            'advisory' => $advisory,
        ]);
    }
}

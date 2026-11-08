<?php

namespace App\Http\Controllers;

use App\Models\GroundwaterStation;
use App\Services\GroundwaterService;
use Illuminate\Http\Request;

class GroundwaterController extends Controller
{
    public function __construct(private GroundwaterService $groundwater)
    {
    }

    public function stations(Request $request)
    {
        $query = GroundwaterStation::query();

        if ($county = $request->query('county')) {
            $query->where('county', 'like', "%{$county}%");
        }
        if ($salinity = $request->query('salinity')) {
            $query->where('salinity_category', 'like', "%{$salinity}%");
        }
        if ($basin = $request->query('basin')) {
            $query->where('basin_name', 'like', "%{$basin}%");
        }

        return response()->json($query->orderBy('county')->orderBy('name')->get());
    }

    public function lookup(Request $request)
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');

        if ($lat === null || $lon === null || $lat === '' || $lon === '') {
            return response()->json(['error' => 'lat and lon query parameters are required'], 400);
        }

        return response()->json(
            $this->groundwater->assess($lat, $lon, $request->query('county', ''), $request->query('elevation', 1500))
        );
    }
}

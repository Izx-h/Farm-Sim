<?php

namespace App\Http\Controllers;

use App\Services\GeoService;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function __construct(private GeoService $geo)
    {
    }

    public function lookup(Request $request)
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');

        if ($lat === null || $lon === null || $lat === '' || $lon === '') {
            return response()->json(['error' => 'Latitude and longitude parameters are required'], 400);
        }

        return response()->json($this->geo->lookup($lat, $lon));
    }
}

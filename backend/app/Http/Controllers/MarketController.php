<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        return response()->json(MarketPrice::orderByDesc('last_updated')->get());
    }

    public function import(Request $request)
    {
        $rows = is_array($request->all()) ? $request->all() : ($request->input('prices', []));

        if (! is_array($rows) || count($rows) === 0) {
            return response()->json(['error' => 'Provide an array of price observations.'], 400);
        }

        $inserted = 0;
        foreach ($rows as $r) {
            if (empty($r['crop_name']) || empty($r['market_name'])) {
                continue;
            }

            MarketPrice::create([
                'crop_name' => (string) $r['crop_name'],
                'market_name' => (string) $r['market_name'],
                'region' => $r['region'] ?? '',
                'unit' => $r['unit'] ?? 'kg',
                'wholesale_price_ksh' => (float) ($r['wholesale_price_ksh'] ?? 0),
                'retail_price_ksh' => (float) ($r['retail_price_ksh'] ?? 0),
                'price_trend' => $r['price_trend'] ?? 'Stable',
                'demand_level' => $r['demand_level'] ?? 'Moderate',
                'last_updated' => $r['observation_date'] ?? $r['last_updated'] ?? now()->toDateString(),
                'source' => 'manual',
                'currency' => 'KSh',
                'is_live' => 0,
                'source_url' => '',
            ]);

            $inserted++;
        }

        return response()->json(['ok' => true, 'inserted' => $inserted, 'source' => 'manual'], 201);
    }
}

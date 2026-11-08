<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\FarmPlan;
use App\Models\SoilTest;
use App\Models\WaterTest;
use App\Services\GroundwaterService;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function __construct(private GroundwaterService $groundwater)
    {
    }

    public function index(Request $request)
    {
        return response()->json(
            Farm::where('user_id', $request->user()->id)->orderByDesc('created_at')->get()
        );
    }

    public function show(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $groundwater = $this->groundwater->assess(
            $farm->latitude,
            $farm->longitude,
            $farm->location_name,
            $farm->elevation_m
        );

        return response()->json([
            ...$farm->toArray(),
            'soilTests' => SoilTest::where('farm_id', $id)->orderByDesc('test_date')->get(),
            'waterTests' => WaterTest::where('farm_id', $id)->orderByDesc('test_date')->get(),
            'plans' => FarmPlan::where('farm_plans.farm_id', $id)
                ->join('crops', 'crops.id', '=', 'farm_plans.crop_id')
                ->select('farm_plans.*', 'crops.name as crop_name', 'crops.category as crop_category')
                ->orderByDesc('farm_plans.created_at')
                ->get(),
            'groundwater' => $groundwater,
        ]);
    }

    public function store(Request $request)
    {
        $b = $request->all();
        $lat = $this->numOr($b['latitude'] ?? null, -0.30);
        $lon = $this->numOr($b['longitude'] ?? null, 36.08);
        $elev = $this->numOr($b['elevation_m'] ?? null, 1800);

        $inferred = $this->groundwater->assess($lat, $lon, $b['location_name'] ?? '', $elev);

        $farm = Farm::create([
            'user_id' => $request->user()->id,
            'name' => $b['name'] ?? 'My Farm',
            'owner' => $b['owner'] ?? $request->user()->name,
            'location_name' => $b['location_name'] ?? 'Kenya',
            'latitude' => $lat,
            'longitude' => $lon,
            'elevation_m' => $elev,
            'slope_deg' => $this->numOr($b['slope_deg'] ?? null, 3.0),
            'soil_type' => $b['soil_type'] ?? 'Loam',
            'soil_ph' => $this->numOr($b['soil_ph'] ?? null, 6.2),
            'nitrogen_level' => $b['nitrogen_level'] ?? 'Medium',
            'phosphorus_level' => $b['phosphorus_level'] ?? 'Medium',
            'potassium_level' => $b['potassium_level'] ?? 'Medium',
            'organic_matter_pct' => $this->numOr($b['organic_matter_pct'] ?? null, 3.0),
            'drainage_class' => $b['drainage_class'] ?? 'Well-drained',
            'water_source' => $b['water_source'] ?? 'Rainfed',
            'area_acres' => $this->numOr($b['area_acres'] ?? null, 1.0),
            'farming_goal' => $b['farming_goal'] ?? 'Mixed',
            'groundwater_depth_m' => $this->numOr($b['groundwater_depth_m'] ?? null, $inferred['water_table_depth_m']),
            'groundwater_yield_m3_hr' => $this->numOr($b['groundwater_yield_m3_hr'] ?? null, $inferred['yield_m3_hr']),
            'groundwater_ec_ds_m' => $this->numOr($b['groundwater_ec_ds_m'] ?? null, $inferred['ec_ds_m']),
            'groundwater_tds_mg_l' => $this->numOr($b['groundwater_tds_mg_l'] ?? null, $inferred['tds_mg_l']),
            'groundwater_salinity_class' => $b['groundwater_salinity_class'] ?? $inferred['salinity_category'],
            'groundwater_irrigation_suitability' => $b['groundwater_irrigation_suitability'] ?? $inferred['irrigation_suitability'],
            'aquifer_basin' => $b['aquifer_basin'] ?? $inferred['aquifer_name'],
            'notes' => $b['notes'] ?? '',
        ]);

        return response()->json($farm, 201);
    }

    public function update(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $farm->fill($request->only([
            'name', 'owner', 'location_name', 'latitude', 'longitude', 'elevation_m',
            'slope_deg', 'soil_type', 'soil_ph', 'nitrogen_level', 'phosphorus_level',
            'potassium_level', 'organic_matter_pct', 'drainage_class', 'water_source',
            'area_acres', 'farming_goal', 'groundwater_depth_m', 'groundwater_yield_m3_hr',
            'groundwater_ec_ds_m', 'groundwater_tds_mg_l', 'groundwater_salinity_class',
            'groundwater_irrigation_suitability', 'aquifer_basin', 'notes',
        ]));
        $farm->save();

        return response()->json($farm);
    }

    public function destroy(Request $request, string $id)
    {
        $deleted = Farm::where('id', $id)->where('user_id', $request->user()->id)->delete();
        if ($deleted === 0) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function storeSoilTest(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $b = $request->all();

        $test = SoilTest::create([
            'farm_id' => $id,
            'test_date' => $b['test_date'] ?? now()->toDateString(),
            'laboratory' => $b['laboratory'] ?? 'National Agro-Lab Services',
            'ph' => $this->n($b['ph'] ?? null),
            'n_ppm' => $this->n($b['n_ppm'] ?? null),
            'p_ppm' => $this->n($b['p_ppm'] ?? null),
            'k_ppm' => $this->n($b['k_ppm'] ?? null),
            'ec_ds_m' => $this->n($b['ec_ds_m'] ?? null),
            'organic_carbon_pct' => $this->n($b['organic_carbon_pct'] ?? null),
            'recommendation_summary' => $b['recommendation_summary'] ?? 'Standard macro-nutrient profile recorded.',
        ]);

        if (! empty($b['ph'])) {
            $farm->update(['soil_ph' => (float) $b['ph']]);
        }

        return response()->json($test, 201);
    }

    public function storeWaterTest(Request $request, string $id)
    {
        $farm = Farm::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $b = $request->all();

        $test = WaterTest::create([
            'farm_id' => $id,
            'test_date' => $b['test_date'] ?? now()->toDateString(),
            'laboratory' => $b['laboratory'] ?? 'Water Resources Authority (WRA) Accredited Lab',
            'source_type' => $b['source_type'] ?? 'Borehole',
            'depth_m' => $this->n($b['depth_m'] ?? null),
            'static_water_level_m' => $this->n($b['static_water_level_m'] ?? null),
            'yield_m3_hr' => $this->n($b['yield_m3_hr'] ?? null),
            'ph' => $this->n($b['ph'] ?? null),
            'ec_ds_m' => $this->n($b['ec_ds_m'] ?? null),
            'tds_mg_l' => $this->n($b['tds_mg_l'] ?? null),
            'sar' => $this->n($b['sar'] ?? null),
            'fluoride_mg_l' => $this->n($b['fluoride_mg_l'] ?? null),
            'salinity_category' => $b['salinity_category'] ?? 'Fresh',
            'irrigation_suitability' => $b['irrigation_suitability'] ?? 'Class 1 - Highly Suitable',
            'notes' => $b['notes'] ?? 'Water laboratory test recorded.',
        ]);

        if (! empty($b['ec_ds_m']) || ! empty($b['tds_mg_l'])) {
            $farm->update([
                'groundwater_ec_ds_m' => $this->n($b['ec_ds_m'] ?? null) ?? $farm->groundwater_ec_ds_m,
                'groundwater_tds_mg_l' => $this->n($b['tds_mg_l'] ?? null) ?? $farm->groundwater_tds_mg_l,
                'groundwater_depth_m' => $this->n($b['static_water_level_m'] ?? null) ?? $farm->groundwater_depth_m,
                'groundwater_yield_m3_hr' => $this->n($b['yield_m3_hr'] ?? null) ?? $farm->groundwater_yield_m3_hr,
                'groundwater_salinity_class' => $b['salinity_category'] ?? $farm->groundwater_salinity_class,
                'groundwater_irrigation_suitability' => $b['irrigation_suitability'] ?? $farm->groundwater_irrigation_suitability,
            ]);
        }

        return response()->json($test, 201);
    }

    private function numOr($value, $fallback)
    {
        $n = $this->n($value);

        return $n !== null ? $n : $fallback;
    }

    private function n($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $num = (float) $value;

        return is_finite($num) ? $num : null;
    }
}

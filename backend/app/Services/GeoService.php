<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Geographic lookup: reverse-geocodes a coordinate via OpenStreetMap Nominatim,
 * fetches elevation via Open-Meteo, then falls back to a bundled Kenyan
 * agro-ecological zone model. Returns a complete auto-importable farm profile.
 */
class GeoService
{
    public function __construct(private GroundwaterService $groundwater)
    {
    }

    public function lookup($lat, $lon): array
    {
        $latitude = (float) $lat;
        $longitude = (float) $lon;

        $locationName = 'Identified Parcel';
        $county = '';
        $country = 'Kenya';
        $elevationM = 1800;

        // 1. Reverse geocode (Nominatim)
        try {
            $res = Http::timeout(3.5)
                ->withHeaders(['User-Agent' => 'AgriPulse-SmartFarming-Platform/1.0 (contact: support@agripulse.app)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 14,
                    'addressdetails' => 1,
                ]);

            if ($res->ok()) {
                $addr = $res->json('address', []);
                $place = $addr['village'] ?? $addr['suburb'] ?? $addr['town']
                    ?? $addr['city'] ?? $addr['neighbourhood'] ?? $addr['county'] ?? 'Farm Zone';
                $county = $addr['county'] ?? $addr['state'] ?? '';
                $country = $addr['country'] ?? 'Kenya';
                $locationName = $county ? "{$place}, {$county}" : $place;
            }
        } catch (\Throwable $e) {
            // fall through to zone inference
        }

        // 2. Elevation (Open-Meteo)
        try {
            $elevRes = Http::timeout(3)->get('https://api.open-meteo.com/v1/elevation', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            if ($elevRes->ok()) {
                $elev = $elevRes->json('elevation');
                if (is_array($elev) && isset($elev[0])) {
                    $elevationM = (int) round($elev[0]);
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }

        // 3. Agro-ecological zone inference
        $agro = $this->inferAgroEcologicalTraits($latitude, $longitude, $elevationM, $locationName);

        if ($locationName === 'Identified Parcel') {
            $locationName = $agro['zoneName'];
        }

        $groundwater = $this->groundwater->assess(
            $latitude,
            $longitude,
            $county ?: $locationName,
            $agro['elevation_m'] ?: $elevationM
        );

        return [
            'latitude' => round($latitude * 10000) / 10000,
            'longitude' => round($longitude * 10000) / 10000,
            'location_name' => $locationName,
            'county' => $county ?: $agro['zoneName'],
            'elevation_m' => $agro['elevation_m'] ?: $elevationM,
            'slope_deg' => $agro['slope_deg'],
            'soil_type' => $agro['soil_type'],
            'soil_ph' => $agro['soil_ph'],
            'nitrogen_level' => $agro['nitrogen_level'],
            'phosphorus_level' => $agro['phosphorus_level'],
            'potassium_level' => $agro['potassium_level'],
            'organic_matter_pct' => $agro['organic_matter_pct'],
            'drainage_class' => $agro['drainage_class'],
            'water_source' => $agro['water_source'],
            'farming_goal' => $agro['farming_goal'],
            'country' => $country,
            'groundwater' => $groundwater,
        ];
    }

    private function inferAgroEcologicalTraits($lat, $lon, $elevationM, $currentName): array
    {
        // Central Highlands
        if ($lat >= -1.2 && $lat <= 0.3 && $lon >= 36.6 && $lon <= 37.8) {
            return [
                'zoneName' => str_contains($currentName, 'Kenya') ? $currentName : 'Central Highlands Ridge',
                'elevation_m' => $elevationM > 500 ? $elevationM : 1950,
                'slope_deg' => 5.5,
                'soil_type' => 'Humic Nitisols (Deep Red Volcanic Loam)',
                'soil_ph' => 5.8,
                'nitrogen_level' => 'High',
                'phosphorus_level' => 'Medium',
                'potassium_level' => 'High',
                'organic_matter_pct' => 4.2,
                'drainage_class' => 'Well-drained',
                'water_source' => 'River / Stream & Sprinkler',
                'farming_goal' => 'Commercial',
            ];
        }

        // Rift Valley Highlands
        if ($lat >= -1.0 && $lat <= 0.8 && $lon >= 35.1 && $lon <= 36.6) {
            return [
                'zoneName' => (str_contains($currentName, 'Nakuru') || str_contains($currentName, 'Rift')) ? $currentName : 'Rift Valley Agricultural Zone',
                'elevation_m' => $elevationM > 500 ? $elevationM : 1850,
                'slope_deg' => 3.5,
                'soil_type' => 'Mollic Andosols (Volcanic Ash / Rich Loam)',
                'soil_ph' => 6.2,
                'nitrogen_level' => 'Medium',
                'phosphorus_level' => 'Medium',
                'potassium_level' => 'High',
                'organic_matter_pct' => 3.8,
                'drainage_class' => 'Well-drained',
                'water_source' => 'Borehole & Drip',
                'farming_goal' => 'Commercial',
            ];
        }

        // Western / Lake Victoria
        if ($lat >= -1.2 && $lat <= 1.0 && $lon >= 34.0 && $lon <= 35.1) {
            return [
                'zoneName' => (str_contains($currentName, 'Kisumu') || str_contains($currentName, 'Lake')) ? $currentName : 'Lake Victoria Agro-Basin',
                'elevation_m' => $elevationM > 500 ? $elevationM : 1180,
                'slope_deg' => 2.0,
                'soil_type' => 'Orthic Ferralsols (Clay Loam)',
                'soil_ph' => 6.6,
                'nitrogen_level' => 'Low',
                'phosphorus_level' => 'Low',
                'potassium_level' => 'Medium',
                'organic_matter_pct' => 2.6,
                'drainage_class' => 'Moderate',
                'water_source' => 'Rainfed & River',
                'farming_goal' => 'Mixed',
            ];
        }

        // Eastern semi-arid
        if ($lat >= -2.5 && $lat <= 0.5 && $lon >= 37.3 && $lon <= 39.0) {
            return [
                'zoneName' => str_contains($currentName, 'Machakos') ? $currentName : 'Eastern Plateau Farm Zone',
                'elevation_m' => $elevationM > 300 ? $elevationM : 1150,
                'slope_deg' => 4.0,
                'soil_type' => 'Ferric Luvisols (Sandy Clay Loam)',
                'soil_ph' => 6.5,
                'nitrogen_level' => 'Low',
                'phosphorus_level' => 'Low',
                'potassium_level' => 'Medium',
                'organic_matter_pct' => 1.9,
                'drainage_class' => 'Well-drained',
                'water_source' => 'Borehole & Drip',
                'farming_goal' => 'Subsistence',
            ];
        }

        // Coastal lowlands
        if ($lat >= -4.7 && $lat <= -2.0 && $lon >= 39.0 && $lon <= 41.5) {
            return [
                'zoneName' => str_contains($currentName, 'Coast') ? $currentName : 'Coastal Agricultural Plain',
                'elevation_m' => $elevationM > 0 ? $elevationM : 80,
                'slope_deg' => 1.5,
                'soil_type' => 'Dystric Arenosols (Sandy Loam)',
                'soil_ph' => 7.0,
                'nitrogen_level' => 'Low',
                'phosphorus_level' => 'Low',
                'potassium_level' => 'Low',
                'organic_matter_pct' => 1.5,
                'drainage_class' => 'Excessively drained',
                'water_source' => 'Rainfed & Borehole',
                'farming_goal' => 'Mixed',
            ];
        }

        // Default
        return [
            'zoneName' => $currentName !== 'Identified Parcel' ? $currentName : sprintf('Field Location (%.2f°, %.2f°)', $lat, $lon),
            'elevation_m' => $elevationM > 0 ? $elevationM : 1500,
            'slope_deg' => $elevationM > 1800 ? 5.0 : 2.5,
            'soil_type' => $elevationM > 1600 ? 'Loam / Volcanic Ash' : 'Sandy Clay Loam',
            'soil_ph' => $elevationM > 1800 ? 5.9 : 6.4,
            'nitrogen_level' => 'Medium',
            'phosphorus_level' => 'Medium',
            'potassium_level' => 'Medium',
            'organic_matter_pct' => 3.0,
            'drainage_class' => 'Well-drained',
            'water_source' => 'Borehole & Drip',
            'farming_goal' => 'Commercial',
        ];
    }
}

<?php

namespace App\Services;

/**
 * Kenya-Wide Groundwater Intelligence and Agricultural Suitability Service.
 *
 * Direct PHP port of the original `groundwaterService.js`. Contains a reference
 * catalogue of 20 modelled WRA/UNESCO borehole stations and an inference model
 * that maps any coordinate to a hydrogeological province, then blends the
 * nearest verified station using a distance-based weighting.
 */
class GroundwaterService
{
    public function stations(): array
    {
        return self::STATIONS;
    }

    public function assess($lat, $lon, $countyHint = '', $elevationM = 1500): array
    {
        $latitude = (float) $lat;
        $longitude = (float) $lon;
        $elev = (float) $elevationM ?: 1500;

        // 1. Nearest verified station
        $nearestStation = null;
        $minDistance = INF;
        foreach (self::STATIONS as $station) {
            $dist = $this->distanceKm($latitude, $longitude, $station['latitude'], $station['longitude']);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $nearestStation = $station + ['distance_km' => $dist];
            }
        }

        // 2. Hydrogeological province
        $province = $this->inferProvince($latitude, $longitude, $elev, $countyHint ?: ($nearestStation['county'] ?? ''));

        $depthM = $province['base_water_table_m'];
        $yieldM3 = $province['base_yield_m3_hr'];
        $ecDsM = $province['base_ec_ds_m'];
        $tdsMgL = $province['base_tds_mg_l'];
        $ph = $province['base_ph'];
        $sar = $province['base_sar'];
        $fluoride = $province['base_fluoride'];

        $confidenceLevel = 'Inferred Agro-Basin Model';
        $confidencePct = 78;

        if ($nearestStation && $nearestStation['distance_km'] <= 15) {
            $confidenceLevel = 'High (Verified Proximal WRA Borehole)';
            $confidencePct = 95;
            $depthM = $nearestStation['water_table_m'];
            $yieldM3 = $nearestStation['yield_m3_hr'];
            $ecDsM = $nearestStation['ec_ds_m'];
            $tdsMgL = $nearestStation['tds_mg_l'];
            $ph = $nearestStation['ph'];
            $sar = $nearestStation['sar'];
            $fluoride = $nearestStation['fluoride_mg_l'];
        } elseif ($nearestStation && $nearestStation['distance_km'] <= 45) {
            $confidenceLevel = 'Moderate (Regional Station Correlation)';
            $confidencePct = 86;
            $w = 0.5;
            $depthM = round($w * $nearestStation['water_table_m'] + (1 - $w) * $depthM);
            $yieldM3 = round(($w * $nearestStation['yield_m3_hr'] + (1 - $w) * $yieldM3) * 10) / 10;
            $ecDsM = round(($w * $nearestStation['ec_ds_m'] + (1 - $w) * $ecDsM) * 100) / 100;
            $tdsMgL = round($w * $nearestStation['tds_mg_l'] + (1 - $w) * $tdsMgL);
            $ph = round(($w * $nearestStation['ph'] + (1 - $w) * $ph) * 10) / 10;
            $sar = round(($w * $nearestStation['sar'] + (1 - $w) * $sar) * 10) / 10;
            $fluoride = round(($w * $nearestStation['fluoride_mg_l'] + (1 - $w) * $fluoride) * 10) / 10;
        }

        // 4. Salinity / suitability classification (FAO / WRA)
        $salinityCategory = 'Fresh';
        $irrigationSuitability = 'Class 1 - Highly Suitable';
        $badgeColor = 'emerald';
        $irrigationNotes = 'Excellent water quality with negligible salinity hazard. Safe for all sensitive horticultural crops and micro-irrigation emitters.';
        $leachingFractionPct = 5;

        if ($ecDsM >= 3.0 || $tdsMgL >= 2000) {
            $salinityCategory = 'Saline to Brackish';
            $irrigationSuitability = 'Class 4 - Severe Salinity Restriction';
            $badgeColor = 'rose';
            $irrigationNotes = 'High salt concentration presents acute osmotic stress and emitter clogging. Restricted to salt-tolerant crops (e.g. Rhodes grass, Sorghum, Barley). Requires +25% leaching fraction and regular soil flushing.';
            $leachingFractionPct = 25;
        } elseif ($ecDsM >= 1.5 || $tdsMgL >= 1000) {
            $salinityCategory = 'Slightly Brackish';
            $irrigationSuitability = 'Class 3 - Elevated Salinity Risk';
            $badgeColor = 'amber';
            $irrigationNotes = 'Elevated dissolved mineral solids. Moderately sensitive crops (avocado, beans, lettuce) will exhibit leaf margin scorch. Recommend 15% leaching fraction and drip scheduling during cool morning hours.';
            $leachingFractionPct = 15;
        } elseif ($ecDsM >= 0.75 || $tdsMgL >= 500) {
            $salinityCategory = 'Moderately Mineralized';
            $irrigationSuitability = 'Class 2 - Suitable with Precautions';
            $badgeColor = 'blue';
            $irrigationNotes = 'Good irrigation water. Minor salinity precautions recommended for high-value sensitive crops like export French beans or greenhouse strawberries.';
            $leachingFractionPct = 10;
        }

        $sodicityRisk = 'Low';
        if ($sar >= 6) {
            $sodicityRisk = 'High';
        } elseif ($sar >= 3) {
            $sodicityRisk = 'Medium';
        }

        if ($yieldM3 >= 20) {
            $yieldRating = 'Very High';
        } elseif ($yieldM3 >= 12) {
            $yieldRating = 'High';
        } elseif ($yieldM3 >= 6) {
            $yieldRating = 'Moderate';
        } else {
            $yieldRating = 'Low';
        }

        if ($depthM < 25) {
            $depthDesc = 'Shallow Water Table (< 25m) - Economical Well Construction';
        } elseif ($depthM <= 60) {
            $depthDesc = 'Moderate Depth (25 - 60m) - Standard Submersible Pump';
        } elseif ($depthM <= 100) {
            $depthDesc = 'Deep Aquifer (60 - 100m) - Heavy-Duty Deep Well Setup';
        } else {
            $depthDesc = 'Ultra-Deep Aquifer (> 100m) - Industrial/Solar Submersible Required';
        }

        $fluorideWarning = $fluoride > 3.0;

        return [
            'aquifer_name' => $province['aquifer_name'],
            'basin_name' => $province['basin_name'],
            'water_table_depth_m' => $depthM,
            'depth_range_desc' => $depthDesc,
            'yield_m3_hr' => $yieldM3,
            'yield_litres_per_day' => (int) round($yieldM3 * 10 * 1000),
            'yield_rating' => $yieldRating,
            'ph' => $ph,
            'ec_ds_m' => $ecDsM,
            'tds_mg_l' => $tdsMgL,
            'sar' => $sar,
            'sodicity_risk' => $sodicityRisk,
            'fluoride_mg_l' => $fluoride,
            'fluoride_warning' => $fluorideWarning,
            'salinity_category' => $salinityCategory,
            'irrigation_suitability' => $irrigationSuitability,
            'badge_color' => $badgeColor,
            'irrigation_notes' => $irrigationNotes,
            'leaching_fraction_pct' => $leachingFractionPct,
            'risk_factors' => $province['risk_factors'],
            'confidence_level' => $confidenceLevel,
            'confidence_pct' => $confidencePct,
            'nearest_station' => $nearestStation ? [
                'code' => $nearestStation['station_code'],
                'name' => $nearestStation['name'],
                'county' => $nearestStation['county'],
                'distance_km' => $nearestStation['distance_km'],
                'source' => $nearestStation['verified_source'],
                'tested_date' => $nearestStation['last_tested_date'],
                'water_table_m' => $nearestStation['water_table_m'],
                'yield_m3_hr' => $nearestStation['yield_m3_hr'],
                'ec_ds_m' => $nearestStation['ec_ds_m'],
                'tds_mg_l' => $nearestStation['tds_mg_l'],
            ] : null,
        ];
    }

    private function distanceKm($lat1, $lon1, $lat2, $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($r * $c * 10) / 10;
    }

    private function inferProvince($lat, $lon, $elevM, $locationName = ''): array
    {
        $loc = strtolower((string) $locationName);

        // Coast
        if ((($lat >= -4.7 && $lat <= -1.8 && $lon >= 38.8 && $lon <= 41.6)
            || str_contains($loc, 'mombasa') || str_contains($loc, 'kilifi') || str_contains($loc, 'kwale')
            || str_contains($loc, 'malindi') || str_contains($loc, 'diani') || str_contains($loc, 'lamu'))) {
            $isCoast = $lon > 39.6;

            return [
                'province' => 'Coastal Sedimentary Basin',
                'aquifer_name' => $isCoast ? 'Coastal Coral Rag & Pleistocene Sand Aquifer' : 'Tiwi-Mazeras Sandstone Freshwater Basin',
                'basin_name' => 'Coast Catchment Basin (WRA Area 6)',
                'base_water_table_m' => $isCoast ? 12 : 28,
                'base_yield_m3_hr' => 22.0,
                'base_ec_ds_m' => $isCoast ? 2.40 : 0.65,
                'base_tds_mg_l' => $isCoast ? 1560 : 420,
                'base_sar' => $isCoast ? 4.8 : 1.5,
                'base_ph' => 7.5,
                'base_fluoride' => 1.2,
                'risk_factors' => $isCoast
                    ? ['Moderate marine seawater intrusion risk near shoreline', 'Potential chloride toxicity for sensitive crops']
                    : ['Stable deep recharge from Shimba Hills'],
            ];
        }

        // Central Highlands
        if ((($lat >= -1.45 && $lat <= 0.4 && $lon >= 36.5 && $lon <= 37.9 && $elevM >= 1400)
            || str_contains($loc, 'kiambu') || str_contains($loc, 'nairobi') || str_contains($loc, 'nyeri')
            || str_contains($loc, 'meru') || str_contains($loc, 'murang') || str_contains($loc, 'kirinyaga')
            || str_contains($loc, 'embu'))) {
            return [
                'province' => 'Central Volcanic Highlands Basin',
                'aquifer_name' => $elevM > 1800 ? 'Aberdare-Mt Kenya Pyroclastic Freshwater Aquifer' : 'Nairobi Trachyte & Upper Athi Volcanic Aquifer',
                'basin_name' => $lat < -1.1 ? 'Athi Catchment Basin (WRA Area 3)' : 'Tana Catchment Basin (WRA Area 4)',
                'base_water_table_m' => $elevM > 1850 ? 25 : 55,
                'base_yield_m3_hr' => 19.5,
                'base_ec_ds_m' => 0.45,
                'base_tds_mg_l' => 290,
                'base_sar' => 1.2,
                'base_ph' => 6.9,
                'base_fluoride' => 1.6,
                'risk_factors' => ['Excellent quality freshwater with low salinity', 'Slightly elevated fluoride in deeper volcanic tuffs'],
            ];
        }

        // Rift Valley
        if ((($lat >= -1.3 && $lat <= 1.2 && $lon >= 35.2 && $lon <= 36.6)
            || str_contains($loc, 'nakuru') || str_contains($loc, 'naivasha') || str_contains($loc, 'baringo')
            || str_contains($loc, 'narok') || str_contains($loc, 'eldoret') || str_contains($loc, 'uasin'))) {
            $isArid = $lat > 0.3 || str_contains($loc, 'baringo') || str_contains($loc, 'marigat');

            return [
                'province' => 'Great Rift Valley Tectonic & Volcanic Aquifers',
                'aquifer_name' => $isArid ? 'Baringo Rift Faulted Volcanic & Alluvial System' : 'Nakuru-Naivasha Pumiceous Tuff & Volcanic Aquifer',
                'basin_name' => 'Rift Valley Basin (WRA Area 2)',
                'base_water_table_m' => $isArid ? 38 : 46,
                'base_yield_m3_hr' => 18.0,
                'base_ec_ds_m' => $isArid ? 2.15 : 0.98,
                'base_tds_mg_l' => $isArid ? 1390 : 640,
                'base_sar' => $isArid ? 4.2 : 2.2,
                'base_ph' => 7.4,
                'base_fluoride' => $isArid ? 5.8 : 4.1,
                'risk_factors' => ['Volcanic geology creates elevated fluoride (3.5 - 6.0 mg/L)', 'Ideal for crops, but requires defluoridation filtration for human drinking water'],
            ];
        }

        // Lake Victoria Basin
        if ((($lat >= -1.5 && $lat <= 1.3 && $lon >= 33.9 && $lon <= 35.1)
            || str_contains($loc, 'kisumu') || str_contains($loc, 'kakamega') || str_contains($loc, 'siaya')
            || str_contains($loc, 'homa') || str_contains($loc, 'bungoma') || str_contains($loc, 'kisii')
            || str_contains($loc, 'busia'))) {
            return [
                'province' => 'Lake Victoria Alluvial & Weathered Basement Basin',
                'aquifer_name' => 'Kavirondo Basin Alluvial & Deep Weathered Regolith Aquifer',
                'basin_name' => $lat < 0 ? 'Lake Victoria South Basin (WRA Area 5)' : 'Lake Victoria North Basin (WRA Area 1)',
                'base_water_table_m' => 16,
                'base_yield_m3_hr' => 17.5,
                'base_ec_ds_m' => 0.52,
                'base_tds_mg_l' => 335,
                'base_sar' => 1.5,
                'base_ph' => 7.1,
                'base_fluoride' => 0.9,
                'risk_factors' => ['High recharge and shallow water table allows economical borehole drilling', 'Safe for all sensitive agricultural crops without leaching restrictions'],
            ];
        }

        // Eastern semi-arid
        if ((($lat >= -2.8 && $lat <= 0.6 && $lon >= 36.8 && $lon <= 38.9)
            || str_contains($loc, 'machakos') || str_contains($loc, 'kitui') || str_contains($loc, 'makueni')
            || str_contains($loc, 'kajiado') || str_contains($loc, 'tharaka'))) {
            return [
                'province' => 'Eastern Basement Complex & Sand Dam Aquifer Belt',
                'aquifer_name' => 'Mozambique Belt Crystalline Basement & Fractured Metamorphic Aquifer',
                'basin_name' => $lat < -1.8 ? 'Athi Catchment Basin (WRA Area 3)' : 'Tana Catchment Basin (WRA Area 4)',
                'base_water_table_m' => 44,
                'base_yield_m3_hr' => 9.0,
                'base_ec_ds_m' => 1.55,
                'base_tds_mg_l' => 995,
                'base_sar' => 3.4,
                'base_ph' => 7.6,
                'base_fluoride' => 2.2,
                'risk_factors' => ['Moderate salinity pockets due to slow basement rock percolation', 'Recommended 15-20% irrigation leaching fraction for salt-sensitive vegetables'],
            ];
        }

        // Northern ASAL
        if ($lat > 1.0 || ($lon > 38.9 && $lat > -1.5)
            || str_contains($loc, 'garissa') || str_contains($loc, 'wajir') || str_contains($loc, 'mandera')
            || str_contains($loc, 'turkana') || str_contains($loc, 'marsabit') || str_contains($loc, 'isiolo')) {
            return [
                'province' => 'Northern Kenya Sedimentary & Deep Palaeo-Basins',
                'aquifer_name' => str_contains($loc, 'turkana') ? 'Lotikipi & Turkwel Deep Sedimentary Aquifer' : 'Merti Palaeo-Channel & Wajir Limestone Confined Aquifer',
                'basin_name' => 'Ewaso Ng’iro North Catchment Basin (WRA Area 7)',
                'base_water_table_m' => 68,
                'base_yield_m3_hr' => 25.0,
                'base_ec_ds_m' => 1.95,
                'base_tds_mg_l' => 1260,
                'base_sar' => 4.5,
                'base_ph' => 7.9,
                'base_fluoride' => 2.6,
                'risk_factors' => ['Deep fossil aquifer storage offers enormous drought security', 'Moderate salinity requires drip irrigation and salt-tolerant forage/date/cereal varieties'],
            ];
        }

        // Default
        return [
            'province' => 'Kenyan Agro-Hydrological Basin',
            'aquifer_name' => 'Regional Stratified Volcanic-Sedimentary Aquifer',
            'basin_name' => 'National Water Resources Authority (WRA) Regulated Basin',
            'base_water_table_m' => 35,
            'base_yield_m3_hr' => 15.0,
            'base_ec_ds_m' => 0.85,
            'base_tds_mg_l' => 545,
            'base_sar' => 2.0,
            'base_ph' => 7.2,
            'base_fluoride' => 1.8,
            'risk_factors' => ['Moderate mineral profile suitable for diversified farming'],
        ];
    }

    private const STATIONS = [
        [
            'id' => 'gw-nak-01', 'station_code' => 'WRA/RV/NAK-042', 'name' => 'Nakuru Central Basin Borehole',
            'county' => 'Nakuru', 'sub_county' => 'Nakuru Town West', 'locality' => 'Rongai / Njoro Plains',
            'latitude' => -0.3031, 'longitude' => 36.0800, 'aquifer_name' => 'Nakuru-Naivasha Rift Volcanic Tuff Aquifer',
            'basin_name' => 'Rift Valley Basin (WRA Area 2)', 'depth_m' => 140, 'water_table_m' => 48,
            'yield_m3_hr' => 16.5, 'ph' => 7.4, 'ec_ds_m' => 1.15, 'tds_mg_l' => 735, 'sar' => 2.8,
            'fluoride_mg_l' => 4.8, 'chloride_mg_l' => 85, 'boron_mg_l' => 0.35,
            'salinity_category' => 'Fresh to Mildly Mineralized', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'Water Resources Authority (WRA) Rift Valley Basin Office', 'confidence_level' => 'High',
            'last_tested_date' => '2025-11-12',
        ],
        [
            'id' => 'gw-nai-02', 'station_code' => 'WRA/RV/NV-108', 'name' => 'Naivasha Horticultural Deep Borehole',
            'county' => 'Nakuru', 'sub_county' => 'Naivasha', 'locality' => 'Moi South Lake Road',
            'latitude' => -0.7600, 'longitude' => 36.3800, 'aquifer_name' => 'Naivasha Lacustrine & Pumice Aquifer',
            'basin_name' => 'Rift Valley Basin (WRA Area 2)', 'depth_m' => 165, 'water_table_m' => 52,
            'yield_m3_hr' => 28.0, 'ph' => 7.2, 'ec_ds_m' => 0.95, 'tds_mg_l' => 610, 'sar' => 2.1,
            'fluoride_mg_l' => 3.9, 'chloride_mg_l' => 64, 'boron_mg_l' => 0.28,
            'salinity_category' => 'Fresh', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'UNESCO IHP-WINS & Naivasha Water Assessment', 'confidence_level' => 'High',
            'last_tested_date' => '2026-02-18',
        ],
        [
            'id' => 'gw-ug-03', 'station_code' => 'WRA/RV/ELD-019', 'name' => 'Uasin Gishu Highland Plateau Well',
            'county' => 'Uasin Gishu', 'sub_county' => 'Moiben', 'locality' => 'Chepkoilel / Sergoit Ridge',
            'latitude' => 0.5830, 'longitude' => 35.3120, 'aquifer_name' => 'Uasin Gishu Phonolite Plateau Aquifer',
            'basin_name' => 'Lake Victoria North Basin (WRA Area 1)', 'depth_m' => 85, 'water_table_m' => 24,
            'yield_m3_hr' => 14.0, 'ph' => 6.8, 'ec_ds_m' => 0.42, 'tds_mg_l' => 270, 'sar' => 1.1,
            'fluoride_mg_l' => 1.1, 'chloride_mg_l' => 28, 'boron_mg_l' => 0.15,
            'salinity_category' => 'Fresh', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'County Hydrogeology Unit & WRA Eldoret', 'confidence_level' => 'High',
            'last_tested_date' => '2026-01-22',
        ],
        [
            'id' => 'gw-bar-04', 'station_code' => 'WRA/RV/BAR-077', 'name' => 'Baringo Lowland Saline Borehole',
            'county' => 'Baringo', 'sub_county' => 'Marigat', 'locality' => 'Perkerra Alluvial Plain',
            'latitude' => 0.4680, 'longitude' => 35.9820, 'aquifer_name' => 'Baringo Rift Faulted Volcanic & Alluvial Aquifer',
            'basin_name' => 'Rift Valley Basin (WRA Area 2)', 'depth_m' => 110, 'water_table_m' => 35,
            'yield_m3_hr' => 12.5, 'ph' => 8.1, 'ec_ds_m' => 2.65, 'tds_mg_l' => 1720, 'sar' => 5.8,
            'fluoride_mg_l' => 7.2, 'chloride_mg_l' => 340, 'boron_mg_l' => 0.85,
            'salinity_category' => 'Slightly Brackish / High Fluoride', 'irrigation_suitability' => 'Class 3 - Elevated Salinity Risk',
            'verified_source' => 'National Irrigation Authority (NIA) & WRA', 'confidence_level' => 'High',
            'last_tested_date' => '2025-09-14',
        ],
        [
            'id' => 'gw-nbi-05', 'station_code' => 'WRA/ATH/NBI-204', 'name' => 'Nairobi Upper Aquifer Observation',
            'county' => 'Nairobi', 'sub_county' => 'Westlands / Karen', 'locality' => 'Dagoretti - Karen Volcanic Formation',
            'latitude' => -1.3190, 'longitude' => 36.7060, 'aquifer_name' => 'Nairobi Trachyte & Kerichwa Valley Tuff Aquifer',
            'basin_name' => 'Athi Catchment Basin (WRA Area 3)', 'depth_m' => 210, 'water_table_m' => 85,
            'yield_m3_hr' => 22.0, 'ph' => 7.1, 'ec_ds_m' => 0.65, 'tds_mg_l' => 415, 'sar' => 1.8,
            'fluoride_mg_l' => 2.1, 'chloride_mg_l' => 42, 'boron_mg_l' => 0.20,
            'salinity_category' => 'Fresh', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'WRA Athi Regional Office & Nairobi City Water Co.', 'confidence_level' => 'High',
            'last_tested_date' => '2026-03-02',
        ],
        [
            'id' => 'gw-kmb-06', 'station_code' => 'WRA/ATH/KMB-088', 'name' => 'Kiambu Agricultural Coffee Belt Well',
            'county' => 'Kiambu', 'sub_county' => 'Githunguri', 'locality' => 'Ikinu Valley Farm Zone',
            'latitude' => -1.0540, 'longitude' => 36.7800, 'aquifer_name' => 'Kiambu Red Clay & Basalt Deep Aquifer',
            'basin_name' => 'Athi Catchment Basin (WRA Area 3)', 'depth_m' => 150, 'water_table_m' => 40,
            'yield_m3_hr' => 18.5, 'ph' => 6.7, 'ec_ds_m' => 0.38, 'tds_mg_l' => 245, 'sar' => 0.9,
            'fluoride_mg_l' => 0.9, 'chloride_mg_l' => 22, 'boron_mg_l' => 0.12,
            'salinity_category' => 'Fresh (Pristine High-Elevation)', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'Ministry of Agriculture & Kiambu County Water Service', 'confidence_level' => 'High',
            'last_tested_date' => '2026-02-10',
        ],
        [
            'id' => 'gw-nyr-07', 'station_code' => 'WRA/TAN/NYR-033', 'name' => 'Nyeri Aberdare Slope Hydrological Station',
            'county' => 'Nyeri', 'sub_county' => 'Tetu', 'locality' => 'Aberdare Foothills Springhead Zone',
            'latitude' => -0.4200, 'longitude' => 36.9500, 'aquifer_name' => 'Aberdare Volcanics & Pyroclastic Aquifer',
            'basin_name' => 'Tana Catchment Basin (WRA Area 4)', 'depth_m' => 75, 'water_table_m' => 18,
            'yield_m3_hr' => 19.0, 'ph' => 6.6, 'ec_ds_m' => 0.28, 'tds_mg_l' => 180, 'sar' => 0.6,
            'fluoride_mg_l' => 0.5, 'chloride_mg_l' => 16, 'boron_mg_l' => 0.08,
            'salinity_category' => 'Fresh (Ultra-Low Mineralization)', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'WRA Tana Basin Area Office (Nyeri)', 'confidence_level' => 'High',
            'last_tested_date' => '2026-01-15',
        ],
        [
            'id' => 'gw-mer-08', 'station_code' => 'WRA/TAN/MER-051', 'name' => 'Meru Mt. Kenya Volcanic Basin Well',
            'county' => 'Meru', 'sub_county' => 'Imenti South', 'locality' => 'Nkubu Agri-Horticultural Cluster',
            'latitude' => 0.0500, 'longitude' => 37.6500, 'aquifer_name' => 'Mount Kenya Basal Aquifer System',
            'basin_name' => 'Tana Catchment Basin (WRA Area 4)', 'depth_m' => 90, 'water_table_m' => 22,
            'yield_m3_hr' => 21.0, 'ph' => 6.9, 'ec_ds_m' => 0.34, 'tds_mg_l' => 220, 'sar' => 0.8,
            'fluoride_mg_l' => 0.7, 'chloride_mg_l' => 19, 'boron_mg_l' => 0.10,
            'salinity_category' => 'Fresh', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'Meru County Hydrogeological Unit', 'confidence_level' => 'High',
            'last_tested_date' => '2026-02-05',
        ],
        [
            'id' => 'gw-mks-09', 'station_code' => 'WRA/ATH/MKS-112', 'name' => 'Machakos Semi-Arid Fractured Rock Well',
            'county' => 'Machakos', 'sub_county' => 'Machakos Town / Kangundo', 'locality' => 'Katangi Agricultural Research Station',
            'latitude' => -1.5167, 'longitude' => 37.2667, 'aquifer_name' => 'Mozambique Belt Metamorphic Fractured Aquifer',
            'basin_name' => 'Athi Catchment Basin (WRA Area 3)', 'depth_m' => 115, 'water_table_m' => 46,
            'yield_m3_hr' => 8.5, 'ph' => 7.6, 'ec_ds_m' => 1.45, 'tds_mg_l' => 940, 'sar' => 3.2,
            'fluoride_mg_l' => 2.4, 'chloride_mg_l' => 165, 'boron_mg_l' => 0.42,
            'salinity_category' => 'Fresh to Moderately Mineralized', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'Kenya Agricultural & Livestock Research Org (KALRO) Katumani', 'confidence_level' => 'High',
            'last_tested_date' => '2025-10-30',
        ],
        [
            'id' => 'gw-kit-10', 'station_code' => 'WRA/TAN/KIT-084', 'name' => 'Kitui Central Basement Aquifer Observation',
            'county' => 'Kitui', 'sub_county' => 'Kitui Central', 'locality' => 'Ithookwe Farm Hub',
            'latitude' => -1.3750, 'longitude' => 38.0100, 'aquifer_name' => 'Kitui Crystalline Basement Complex & Sandy Riverbed Sand Dam Aquifer',
            'basin_name' => 'Tana Catchment Basin (WRA Area 4)', 'depth_m' => 95, 'water_table_m' => 38,
            'yield_m3_hr' => 6.8, 'ph' => 7.8, 'ec_ds_m' => 1.85, 'tds_mg_l' => 1205, 'sar' => 4.1,
            'fluoride_mg_l' => 2.8, 'chloride_mg_l' => 240, 'boron_mg_l' => 0.55,
            'salinity_category' => 'Slightly Brackish', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'Kitui County Ministry of Water and Irrigation', 'confidence_level' => 'High',
            'last_tested_date' => '2025-12-04',
        ],
        [
            'id' => 'gw-mak-11', 'station_code' => 'WRA/ATH/MAK-045', 'name' => 'Makueni Kibwezi Horticultural Borehole',
            'county' => 'Makueni', 'sub_county' => 'Kibwezi East', 'locality' => 'Kibwezi River Alluvium & Chyulu Volcanic Transition',
            'latitude' => -2.4167, 'longitude' => 37.9667, 'aquifer_name' => 'Chyulu Hills Basalt & Alluvial Aquifer',
            'basin_name' => 'Athi Catchment Basin (WRA Area 3)', 'depth_m' => 130, 'water_table_m' => 34,
            'yield_m3_hr' => 24.5, 'ph' => 7.3, 'ec_ds_m' => 0.88, 'tds_mg_l' => 565, 'sar' => 1.9,
            'fluoride_mg_l' => 1.4, 'chloride_mg_l' => 55, 'boron_mg_l' => 0.22,
            'salinity_category' => 'Fresh (Chyulu Springs Recharge)', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'University of Nairobi Hydrogeology Research & WRA', 'confidence_level' => 'High',
            'last_tested_date' => '2026-01-19',
        ],
        [
            'id' => 'gw-ksm-12', 'station_code' => 'WRA/LV/KSM-021', 'name' => 'Kisumu Kano Plains Agricultural Well',
            'county' => 'Kisumu', 'sub_county' => 'Nyando', 'locality' => 'Ahero Rice Scheme Aquifer Buffer',
            'latitude' => -0.1740, 'longitude' => 34.9200, 'aquifer_name' => 'Kavirondo Rift Alluvial & Lacustrine Sediments',
            'basin_name' => 'Lake Victoria South Basin (WRA Area 5)', 'depth_m' => 65, 'water_table_m' => 14,
            'yield_m3_hr' => 22.0, 'ph' => 7.5, 'ec_ds_m' => 0.82, 'tds_mg_l' => 525, 'sar' => 2.4,
            'fluoride_mg_l' => 1.3, 'chloride_mg_l' => 48, 'boron_mg_l' => 0.21,
            'salinity_category' => 'Fresh', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'National Irrigation Authority (Ahero) & WRA Kisumu', 'confidence_level' => 'High',
            'last_tested_date' => '2026-02-14',
        ],
        [
            'id' => 'gw-kak-13', 'station_code' => 'WRA/LV/KAK-066', 'name' => 'Kakamega Forest Margin High Recharge Station',
            'county' => 'Kakamega', 'sub_county' => 'Lurambi', 'locality' => 'Kakamega Town / Masinde Muliro Ag-zone',
            'latitude' => 0.2833, 'longitude' => 34.7500, 'aquifer_name' => 'Kakamega Granitoid & Deep Weathered Regolith Aquifer',
            'basin_name' => 'Lake Victoria North Basin (WRA Area 1)', 'depth_m' => 55, 'water_table_m' => 12,
            'yield_m3_hr' => 16.0, 'ph' => 6.5, 'ec_ds_m' => 0.31, 'tds_mg_l' => 200, 'sar' => 0.7,
            'fluoride_mg_l' => 0.4, 'chloride_mg_l' => 18, 'boron_mg_l' => 0.09,
            'salinity_category' => 'Fresh (High Rainfall Recharged)', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'Lake Victoria North Water Works Agency', 'confidence_level' => 'High',
            'last_tested_date' => '2026-01-28',
        ],
        [
            'id' => 'gw-homa-14', 'station_code' => 'WRA/LV/HB-034', 'name' => 'Homa Bay Lake Fringe Observation Station',
            'county' => 'Homa Bay', 'sub_county' => 'Mbita', 'locality' => 'Rusinga / Mbita Agro-Fisheries Cluster',
            'latitude' => -0.4260, 'longitude' => 34.2050, 'aquifer_name' => 'Homa Volcanic Complex & Lake Sediments',
            'basin_name' => 'Lake Victoria South Basin (WRA Area 5)', 'depth_m' => 80, 'water_table_m' => 19,
            'yield_m3_hr' => 11.0, 'ph' => 7.9, 'ec_ds_m' => 1.55, 'tds_mg_l' => 990, 'sar' => 3.8,
            'fluoride_mg_l' => 3.5, 'chloride_mg_l' => 145, 'boron_mg_l' => 0.45,
            'salinity_category' => 'Moderately Mineralized', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'WRA Homa Bay Sub-Regional Office', 'confidence_level' => 'High',
            'last_tested_date' => '2025-11-20',
        ],
        [
            'id' => 'gw-msa-15', 'station_code' => 'WRA/COA/MSA-011', 'name' => 'Mombasa Island Coastal Saline Interface',
            'county' => 'Mombasa', 'sub_county' => 'Kisauni', 'locality' => 'Bamburi Coastal Strip',
            'latitude' => -4.0150, 'longitude' => 39.7150, 'aquifer_name' => 'Kilindini Sand & Pleistocene Coral Reef Limestone Aquifer',
            'basin_name' => 'Coast Catchment Basin (WRA Area 6)', 'depth_m' => 45, 'water_table_m' => 8,
            'yield_m3_hr' => 15.0, 'ph' => 8.2, 'ec_ds_m' => 3.85, 'tds_mg_l' => 2510, 'sar' => 7.4,
            'fluoride_mg_l' => 1.8, 'chloride_mg_l' => 680, 'boron_mg_l' => 0.95,
            'salinity_category' => 'Brackish (Marine Intrusion Risk)', 'irrigation_suitability' => 'Class 4 - Severe Salinity Restriction',
            'verified_source' => 'Coast Water Works Development Agency & WRA Coast', 'confidence_level' => 'High',
            'last_tested_date' => '2026-02-25',
        ],
        [
            'id' => 'gw-kwa-16', 'station_code' => 'WRA/COA/KWA-049', 'name' => 'Kwale Tiwi Premium Fresh Sandstone Aquifer',
            'county' => 'Kwale', 'sub_county' => 'Matuga', 'locality' => 'Tiwi Sandstone Ridge',
            'latitude' => -4.2400, 'longitude' => 39.5800, 'aquifer_name' => 'Tiwi Mazeras Sandstone Deep Freshwater Aquifer',
            'basin_name' => 'Coast Catchment Basin (WRA Area 6)', 'depth_m' => 95, 'water_table_m' => 22,
            'yield_m3_hr' => 32.0, 'ph' => 7.0, 'ec_ds_m' => 0.58, 'tds_mg_l' => 370, 'sar' => 1.4,
            'fluoride_mg_l' => 0.8, 'chloride_mg_l' => 38, 'boron_mg_l' => 0.16,
            'salinity_category' => 'Fresh (Prime Coastal Sandstone)', 'irrigation_suitability' => 'Class 1 - Highly Suitable',
            'verified_source' => 'UNESCO Coastal Aquifer Study & Kwale Water Co.', 'confidence_level' => 'High',
            'last_tested_date' => '2026-03-01',
        ],
        [
            'id' => 'gw-klf-17', 'station_code' => 'WRA/COA/KLF-078', 'name' => 'Kilifi Sabaki Alluvial Borehole',
            'county' => 'Kilifi', 'sub_county' => 'Malindi', 'locality' => 'Sabaki River Delta Farm Scheme',
            'latitude' => -3.1500, 'longitude' => 40.1100, 'aquifer_name' => 'Sabaki River Alluvial & Magarini Sand Aquifer',
            'basin_name' => 'Coast Catchment Basin (WRA Area 6)', 'depth_m' => 60, 'water_table_m' => 14,
            'yield_m3_hr' => 25.0, 'ph' => 7.6, 'ec_ds_m' => 1.35, 'tds_mg_l' => 875, 'sar' => 3.1,
            'fluoride_mg_l' => 1.2, 'chloride_mg_l' => 140, 'boron_mg_l' => 0.38,
            'salinity_category' => 'Fresh to Moderately Mineralized', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'Kilifi County Water Dept & National Irrigation Board', 'confidence_level' => 'High',
            'last_tested_date' => '2025-12-15',
        ],
        [
            'id' => 'gw-gar-18', 'station_code' => 'WRA/ENN/GAR-091', 'name' => 'Garissa Merti Strategic Aquifer Well',
            'county' => 'Garissa', 'sub_county' => 'Fafi / Bura', 'locality' => 'Habaswein - Merti Palaeo-channel Boundary',
            'latitude' => -0.4530, 'longitude' => 39.6540, 'aquifer_name' => 'Merti Palaeo-Drainage Confined Sand Aquifer',
            'basin_name' => 'Ewaso Ng’iro North Basin (WRA Area 7)', 'depth_m' => 155, 'water_table_m' => 72,
            'yield_m3_hr' => 28.0, 'ph' => 7.8, 'ec_ds_m' => 1.62, 'tds_mg_l' => 1040, 'sar' => 3.9,
            'fluoride_mg_l' => 1.9, 'chloride_mg_l' => 210, 'boron_mg_l' => 0.48,
            'salinity_category' => 'Slightly Brackish (Deep Confined Fossil Water)', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'WRA Ewaso Ng’iro North & UNICEF WASH', 'confidence_level' => 'High',
            'last_tested_date' => '2025-10-18',
        ],
        [
            'id' => 'gw-tur-19', 'station_code' => 'WRA/RV/TUR-102', 'name' => 'Turkana Lotikipi Hydrogeological Station',
            'county' => 'Turkana', 'sub_county' => 'Turkana West / Lodwar', 'locality' => 'Turkwel Alluvial Aquifer Zone',
            'latitude' => 3.1167, 'longitude' => 35.6000, 'aquifer_name' => 'Turkwel Alluvium & Lotikipi Basin Sandstone Aquifer',
            'basin_name' => 'Rift Valley Basin (WRA Area 2)', 'depth_m' => 90, 'water_table_m' => 26,
            'yield_m3_hr' => 35.0, 'ph' => 7.7, 'ec_ds_m' => 1.25, 'tds_mg_l' => 810, 'sar' => 2.9,
            'fluoride_mg_l' => 3.1, 'chloride_mg_l' => 110, 'boron_mg_l' => 0.32,
            'salinity_category' => 'Fresh to Moderately Mineralized', 'irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'verified_source' => 'Turkana County Water Affairs & UNESCO Groundwater Project', 'confidence_level' => 'High',
            'last_tested_date' => '2026-01-08',
        ],
        [
            'id' => 'gw-waj-20', 'station_code' => 'WRA/ENN/WAJ-015', 'name' => 'Wajir Shallow Limestone Aquifer Monitoring',
            'county' => 'Wajir', 'sub_county' => 'Wajir East', 'locality' => 'Wajir Township Limestone Aquifer',
            'latitude' => 1.7500, 'longitude' => 40.0500, 'aquifer_name' => 'Wajir Weathered Limestone & Sand Aquifer',
            'basin_name' => 'Ewaso Ng’iro North Basin (WRA Area 7)', 'depth_m' => 35, 'water_table_m' => 11,
            'yield_m3_hr' => 9.5, 'ph' => 8.0, 'ec_ds_m' => 2.95, 'tds_mg_l' => 1920, 'sar' => 6.2,
            'fluoride_mg_l' => 2.5, 'chloride_mg_l' => 410, 'boron_mg_l' => 0.70,
            'salinity_category' => 'Brackish (High Evaporite / Nitrates)', 'irrigation_suitability' => 'Class 3 - Elevated Salinity Risk',
            'verified_source' => 'Wajir County Water Office & WRA', 'confidence_level' => 'High',
            'last_tested_date' => '2025-11-05',
        ],
    ];
}

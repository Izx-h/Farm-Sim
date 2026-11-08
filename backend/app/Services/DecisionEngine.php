<?php

namespace App\Services;

use App\Models\Farm;
use App\Services\GroundwaterService;

/**
 * Smart Agricultural Decision-Support & Suitability Engine.
 *
 * Direct PHP port of the original `decisionEngine.js`. Scores a crop against a
 * farm's agronomic conditions and produces strengths, limiting factors and
 * corrective interventions, plus a full commercial economics model and a
 * soil-test-based fertilizer recommendation engine.
 */
class DecisionEngine
{
    public function evaluateCropSuitability(Farm $farm, $crop): array
    {
        $score = 100;
        $strengths = [];
        $limitingFactors = [];
        $interventions = [];

        // 1. Soil pH
        $ph = (float) $farm->soil_ph;
        $optMin = (float) $crop->optimal_ph_min;
        $optMax = (float) $crop->optimal_ph_max;
        $minPh = (float) $crop->min_ph;
        $maxPh = (float) $crop->max_ph;

        if ($ph >= $optMin && $ph <= $optMax) {
            $strengths[] = "Soil pH ({$ph}) is within the optimal range ({$optMin} - {$optMax}).";
        } elseif ($ph >= $minPh && $ph <= $maxPh) {
            $score -= 10;
            $limitingFactors[] = "Soil pH ({$ph}) is tolerable but outside optimal bounds ({$optMin} - {$optMax}).";
            if ($ph < $optMin) {
                $interventions[] = 'Apply agricultural limestone or dolomite at 300-500 kg/acre to gently elevate pH before planting.';
            } else {
                $interventions[] = 'Incorporate acidic organic compost or elemental sulfur to moderate soil pH.';
            }
        } else {
            $score -= 35;
            $limitingFactors[] = "Soil pH ({$ph}) is critically outside the survival range ({$minPh} - {$maxPh}). High risk of nutrient lockout.";
            $interventions[] = 'Requires major soil conditioning and liming 4 weeks prior to any sowing.';
        }

        // 2. Elevation
        $elev = (float) $farm->elevation_m;
        $minElev = (float) $crop->min_elevation_m;
        $maxElev = (float) $crop->max_elevation_m;

        if ($elev >= $minElev && $elev <= $maxElev) {
            $strengths[] = "Farm altitude ({$elev}m) matches the agro-ecological altitude requirement ({$minElev}m - {$maxElev}m).";
        } else {
            $diff = $elev < $minElev ? $minElev - $elev : $elev - $maxElev;
            $penalty = min(30, (int) round(($diff / 250) * 10));
            $score -= $penalty;
            $limitingFactors[] = "Farm altitude ({$elev}m) deviates from ideal altitude ({$minElev}m - {$maxElev}m).";
            if ($elev > $maxElev) {
                $interventions[] = 'Expect longer maturity cycle due to cooler night temperatures; choose cold-tolerant varieties.';
            } else {
                $interventions[] = 'Warm valley temperatures may accelerate evapotranspiration; ensure supplemental moisture.';
            }
        }

        // 3. Slope & topography
        $slope = (float) $farm->slope_deg;
        if ($slope > 12) {
            if ($crop->category === 'Tuber' || $crop->category === 'Vegetable') {
                $score -= 20;
                $limitingFactors[] = "Steep slope ({$slope}°) poses severe soil erosion and water runoff risks for tilled row crops.";
                $interventions[] = 'Construct fanya-juu terraces, contour vegetative strips (vetiver grass), and practice minimum tillage.';
            } else {
                $score -= 10;
                $limitingFactors[] = 'Steep terrain requires contour planting to curb runoff.';
                $interventions[] = 'Establish contour bunds and plant across the slope.';
            }
        } elseif ($slope <= 5) {
            $strengths[] = "Gentle topography ({$slope}°) facilitates uniform mechanized operations and low erosion hazard.";
        }

        // 4. Drainage & soil type
        $drainage = $farm->drainage_class;
        if ($crop->flood_tolerance === 'Low' && ($drainage === 'Poor' || $drainage === 'Waterlogged')) {
            $score -= 25;
            $limitingFactors[] = "Poor soil drainage is hazardous for {$crop->name}, triggering root rot and stunted tuber formation.";
            $interventions[] = 'Construct raised beds (25-30cm height) and install perimeter drainage trenches.';
        } elseif ($drainage === 'Well-drained') {
            $strengths[] = 'Well-drained soil promotes robust root respiration and active mycorrhizal growth.';
        }

        // 5. Water availability & groundwater salinity
        $waterSource = $farm->water_source ?? '';
        $isIrrigated = str_contains($waterSource, 'Drip')
            || str_contains($waterSource, 'Borehole')
            || str_contains($waterSource, 'Sprinkler')
            || str_contains($waterSource, 'River')
            || str_contains($waterSource, 'Well');

        $gw = ($farm->groundwater_ec_ds_m !== null && $farm->groundwater_ec_ds_m !== '')
            ? [
                'ec_ds_m' => (float) $farm->groundwater_ec_ds_m,
                'tds_mg_l' => (float) ($farm->groundwater_tds_mg_l ?: 500),
                'yield_m3_hr' => (float) ($farm->groundwater_yield_m3_hr ?: 15),
                'water_table_depth_m' => (float) ($farm->groundwater_depth_m ?: 35),
                'salinity_category' => $farm->groundwater_salinity_class ?: 'Fresh',
                'irrigation_suitability' => $farm->groundwater_irrigation_suitability ?: 'Class 1 - Highly Suitable',
                'aquifer_name' => $farm->aquifer_basin ?: 'Local Aquifer Basin',
                'leaching_fraction_pct' => 12,
            ]
            : app(GroundwaterService::class)->assess(
                $farm->latitude,
                $farm->longitude,
                $farm->location_name,
                $farm->elevation_m
            );

        $cropSalinityTolerance = [
            'French Beans' => 1.0, 'Avocado' => 1.2, 'Onions' => 1.2, 'Strawberries' => 1.0,
            'Irish Potatoes' => 1.7, 'Capsicum (Sweet Pepper)' => 1.5, 'Tomatoes' => 2.3,
            'Cabbage' => 2.5, 'Sukuma Wiki (Collard Greens)' => 2.8, 'Hybrid Maize' => 2.5,
            'Sorghum' => 4.5, 'Finger Millet' => 3.8, 'Rhodes Grass' => 6.0, 'Barley' => 5.5,
            'Coffee' => 1.5, 'Tea' => 1.0,
        ];

        $cropEcLimit = (float) ($crop->salinity_tolerance_ec_ds_m
            ?: ($cropSalinityTolerance[$crop->name]
                ?? ($crop->category === 'Vegetable' ? 1.5 : ($crop->category === 'Fruit' ? 1.2 : 2.5))));

        if ($isIrrigated) {
            if (str_contains($waterSource, 'Borehole') || str_contains($waterSource, 'Well') || str_contains($waterSource, 'Drip')) {
                if ($gw['ec_ds_m'] > $cropEcLimit) {
                    $excess = $gw['ec_ds_m'] - $cropEcLimit;
                    $penalty = min(28, (int) round($excess * 14));
                    $score -= $penalty;
                    $limitingFactors[] = "Groundwater salinity ({$gw['ec_ds_m']} dS/m, TDS {$gw['tds_mg_l']} mg/L in {$gw['aquifer_name']}) exceeds the salinity sensitivity threshold for {$crop->name} ({$cropEcLimit} dS/m). Risk of osmotic moisture stress and leaf tip burn.";
                    $interventions[] = 'Apply a leaching fraction of +'.($gw['leaching_fraction_pct'] ?? 15).'% extra water volume, schedule evening drip cycles, or blend borehole water with harvested rainwater.';
                } else {
                    $strengths[] = "Groundwater quality from {$gw['aquifer_name']} (EC {$gw['ec_ds_m']} dS/m, TDS {$gw['tds_mg_l']} mg/L) is within the safe salinity threshold for {$crop->name}. Sustainable yield: {$gw['yield_m3_hr']} m³/hr.";
                }
            } else {
                $strengths[] = "Irrigation access ({$waterSource}) mitigates seasonal rainfall deficits, ensuring uninterrupted moisture.";
            }
        } else {
            if ($crop->drought_tolerance === 'Low') {
                $score -= 15;
                $limitingFactors[] = 'Crop has low drought tolerance and relies entirely on rainfed moisture. Mid-season dry spells present yield risk.';
                $interventions[] = "Consider tapping available groundwater ({$gw['aquifer_name']}, water table at ~{$gw['water_table_depth_m']}m, yield ~{$gw['yield_m3_hr']} m³/hr) for supplemental drip irrigation.";
            } elseif ($crop->drought_tolerance === 'High' || $crop->drought_tolerance === 'Very High') {
                $strengths[] = 'High drought tolerance makes this crop exceptionally resilient to rainfed dry spells.';
            }
        }

        // 6. Soil fertility
        if ($farm->nitrogen_level === 'Low') {
            if ($crop->category !== 'Legume') {
                $score -= 8;
                $limitingFactors[] = 'Soil nitrogen is Low. Cereal/vegetable leaf canopy expansion will suffer without prompt nitrogen top-dressing.';
                $interventions[] = 'Split-apply CAN or urea fertilizer at weeks 3 and 6, or incorporate well-cured compost.';
            } else {
                $strengths[] = 'Legume will biologically fix atmospheric nitrogen, overcoming low soil nitrogen status.';
            }
        }

        if ($farm->phosphorus_level === 'Low') {
            $score -= 10;
            $limitingFactors[] = 'Soil phosphorus is Low. Crucial for early root establishment and energy transfer.';
            $interventions[] = 'Apply basal DAP or Single Superphosphate (SSP) directly in planting furrows.';
        }

        if ((float) $farm->organic_matter_pct < 2.5) {
            $score -= 7;
            $limitingFactors[] = "Organic matter is low ({$farm->organic_matter_pct}%). Soil has lower moisture-retention capacity.";
            $interventions[] = 'Incorporate 3-5 tons/acre of farmyard manure prior to planting.';
        } else {
            $strengths[] = "Good organic matter content ({$farm->organic_matter_pct}%) boosts moisture and cation exchange capacity.";
        }

        // 7. Farming objective alignment
        if ($farm->farming_goal === 'Subsistence') {
            if ($crop->food_security_rating >= 8) {
                $score += 5;
                $strengths[] = 'Excellent match for household food security with dependable staple caloric/protein yield.';
            } elseif ($crop->commercial_value_rating >= 9 && $crop->food_security_rating <= 5) {
                $score -= 10;
                $limitingFactors[] = 'High input cost commercial crop may strain subsistence farm budget without local buyer off-take.';
            }
        } elseif ($farm->farming_goal === 'Commercial') {
            if ($crop->commercial_value_rating >= 8) {
                $score += 5;
                $strengths[] = "High commercial value rating ({$crop->commercial_value_rating}/10) aligns with profit maximization goals.";
            } elseif ($crop->commercial_value_rating <= 6) {
                $score -= 5;
                $limitingFactors[] = 'Lower commercial return per acre compared to high-value horticultural alternatives.';
            }
        }

        $finalScore = max(18, min(98, (int) round($score)));

        $category = 'Highly Suitable';
        $badgeColor = 'emerald';
        if ($finalScore < 50) {
            $category = 'Not Recommended';
            $badgeColor = 'rose';
        } elseif ($finalScore < 70) {
            $category = 'Marginally Suitable';
            $badgeColor = 'amber';
        } elseif ($finalScore < 85) {
            $category = 'Moderately Suitable';
            $badgeColor = 'blue';
        }

        return [
            'cropId' => $crop->id,
            'cropName' => $crop->name,
            'category' => $crop->category,
            'suitabilityScore' => $finalScore,
            'categoryLabel' => $category,
            'badgeColor' => $badgeColor,
            'maturityDays' => $crop->maturity_days,
            'projectedYieldTons' => $crop->average_yield_tons_per_acre,
            'marketPricePerKg' => $crop->market_price_per_kg,
            'strengths' => $strengths,
            'limitingFactors' => $limitingFactors,
            'interventions' => $interventions,
            'isRecommended' => $finalScore >= 65,
        ];
    }

    /**
     * Commercial budget & financial profitability simulator.
     */
    public function calculateCropEconomics($crop, $acres = 1, array $options = []): array
    {
        $area = (float) $acres > 0 ? (float) $acres : 1;
        $pricePerKg = isset($options['currentMarketPrice']) && $options['currentMarketPrice']
            ? (float) $options['currentMarketPrice']
            : (float) ($crop->market_price_per_kg ?? 0);

        $baseYield = (float) ($crop->average_yield_tons_per_acre ?? 0);

        $perAcre = [
            'landPreparation' => 6000,
            'seeds' => (float) ($crop->seed_cost_per_acre ?? 0),
            'fertilizer' => (float) ($crop->fertilizer_cost_per_acre ?? 0),
            'cropProtection' => $this->pestProtectionDefault($crop->category),
            'irrigation' => $this->irrigationDefault($crop),
            'labour' => (float) ($crop->labor_cost_per_acre ?? 0),
            'machinery' => 6000,
            'transportPackaging' => $this->transportDefault($crop->category),
            'other' => 2500,
        ];

        $scale = function (float $mult) use ($perAcre, $area): array {
            $out = [];
            foreach ($perAcre as $k => $v) {
                $out[$k] = (int) round($v * $mult * $area);
            }

            return $out;
        };

        $costExpected = $scale(1);
        $costConservative = $scale(1.10);
        $costOptimistic = $scale(0.95);

        $yieldTons = [
            'conservative' => $this->round1($baseYield * 0.75 * $area),
            'expected' => $this->round1($baseYield * $area),
            'optimistic' => $this->round1($baseYield * 1.20 * $area),
        ];

        $price = [
            'conservative' => $this->round2($pricePerKg * 0.90),
            'expected' => $this->round2($pricePerKg),
            'optimistic' => $this->round2($pricePerKg * 1.10),
        ];

        $scenarios = [];
        $totalCosts = [];

        foreach (['conservative', 'expected', 'optimistic'] as $key) {
            $costs = $key === 'conservative' ? $costConservative : ($key === 'optimistic' ? $costOptimistic : $costExpected);
            $totalCost = array_sum($costs);
            $revenue = (int) round($yieldTons[$key] * 1000 * $price[$key]);
            $grossMargin = $revenue - $this->sumVariable($costs);
            $netProfit = $revenue - $totalCost;
            $roi = $totalCost > 0 ? $this->round1(($netProfit / $totalCost) * 100) : 0;

            $totalCosts[$key] = $totalCost;
            $scenarios[$key] = [
                'assumptions' => [
                    'yieldPct' => $key === 'conservative' ? 75 : ($key === 'optimistic' ? 120 : 100),
                    'pricePct' => $key === 'conservative' ? 90 : ($key === 'optimistic' ? 110 : 100),
                    'costPct' => $key === 'conservative' ? 110 : ($key === 'optimistic' ? 95 : 100),
                    'note' => $key === 'conservative'
                        ? 'Adverse season: low yield, soft prices, higher input cost'
                        : ($key === 'optimistic'
                            ? 'Good season: high yield, firm prices, input efficiency'
                            : 'Standard practice: baseline yield, price and cost'),
                ],
                'yieldTons' => $yieldTons[$key],
                'marketableKg' => (int) round($yieldTons[$key] * 1000),
                'pricePerKg' => $price[$key],
                'revenue' => $revenue,
                'grossMargin' => $grossMargin,
                'netProfit' => $netProfit,
                'roiPercent' => $roi,
                'profitMarginPct' => $revenue > 0 ? (int) round(($netProfit / $revenue) * 100) : 0,
                'costPerAcre' => (int) round($totalCost / $area),
                'costPerKg' => $yieldTons[$key] > 0 ? $this->round2($totalCost / ($yieldTons[$key] * 1000)) : null,
            ];
        }

        $expectedYieldKg = $yieldTons['expected'] * 1000;
        $breakEvenPricePerKg = $expectedYieldKg > 0 ? $this->round2($totalCosts['expected'] / $expectedYieldKg) : null;
        $breakEvenYieldTons = $pricePerKg > 0 ? $this->round1($totalCosts['expected'] / ($pricePerKg * 1000)) : null;

        return [
            'cropName' => $crop->name,
            'acres' => $area,
            'marketPricePerKg' => $this->round2($pricePerKg),
            'currency' => 'KSh',
            'formulas' => [
                'grossRevenue' => 'marketable yield (kg) × selling price (KSh/kg)',
                'grossMargin' => 'gross revenue − variable production costs',
                'netProfit' => 'gross revenue − total production costs (incl. overheads)',
                'breakEvenPrice' => 'total production cost ÷ expected marketable yield (kg)',
                'breakEvenYield' => 'total production cost ÷ selling price per kg ÷ 1000',
                'roi' => 'net profit ÷ total production cost (investment base) × 100',
                'costBasis' => 'Total production cost includes land prep, seed, fertilizer, crop protection, irrigation, labour, machinery, transport/packaging and other.',
            ],
            'costs' => $costExpected + ['totalCost' => $totalCosts['expected']],
            'scenarios' => $scenarios,
            'breakEven' => [
                'pricePerKg' => $breakEvenPricePerKg,
                'yieldTons' => $breakEvenYieldTons,
                'roiPercentExpected' => $scenarios['expected']['roiPercent'],
            ],
        ];
    }

    /**
     * Soil-test-based fertilizer recommendation engine.
     */
    public function calculateFertilizerRecommendation($crop, $soilTest, $areaAcres = 1): array
    {
        $area = (float) $areaAcres > 0 ? (float) $areaAcres : 1;
        $demand = self::CROP_NUTRIENT_DEMAND[$crop->category] ?? self::CROP_NUTRIENT_DEMAND['Cereal'];

        $hasLabData = $soilTest && (
            ($soilTest->n_ppm ?? null) !== null
            || ($soilTest->p_ppm ?? null) !== null
            || ($soilTest->k_ppm ?? null) !== null
        );

        $estAvailable = [
            'n' => ($soilTest->n_ppm ?? null) !== null ? $soilTest->n_ppm * 2 : null,
            'p2o5' => ($soilTest->p_ppm ?? null) !== null ? $soilTest->p_ppm * 2.29 * 0.44 : null,
            'k2o' => ($soilTest->k_ppm ?? null) !== null ? $soilTest->k_ppm * 2 : null,
        ];

        $recommendations = [];
        $missing = [];

        foreach (['n', 'p2o5', 'k2o'] as $nutrient) {
            $required = $demand[$nutrient];
            if ($estAvailable[$nutrient] === null) {
                $missing[] = strtoupper($nutrient);
                $recommendations[] = [
                    'nutrient' => $nutrient,
                    'basis' => 'general',
                    'note' => 'No laboratory '.strtoupper($nutrient).' result. Provide a soil test for a precise rate.',
                ];
                continue;
            }
            $available = $estAvailable[$nutrient];
            $deficit = max(0, $required - $available);
            $fertilizerNeeded = $deficit > 0 ? $deficit / self::RECOVERY[$nutrient] : 0;
            $recommendations[] = [
                'nutrient' => $nutrient,
                'basis' => 'laboratory',
                'requiredKgPerAcre' => $required,
                'estimatedAvailableKgPerAcre' => $this->round1($available),
                'deficitKgPerAcre' => $this->round1($deficit),
                'fertilizerProductKgPerAcre' => $this->round1($fertilizerNeeded),
                'fertilizerProductKgTotal' => $this->round1($fertilizerNeeded * $area),
                'level' => $this->ppmLevelFor($nutrient, $soilTest),
            ];
        }

        return [
            'crop' => $crop->name,
            'category' => $crop->category,
            'areaAcres' => $area,
            'dataBasis' => $hasLabData ? 'laboratory' : 'general',
            'disclaimer' => $hasLabData
                ? 'Recommendation based on laboratory soil-test nutrients where present. Rates are estimates; adjust after agronomist review and split applications.'
                : 'No laboratory soil nutrient results were supplied. The following is general category-level guidance, NOT a soil-test-based prescription.',
            'missingInputs' => $missing,
            'nutrients' => $recommendations,
            'products' => self::FERTILIZERS,
            'formula' => 'deficit (kg/acre) = max(0, crop requirement − estimated available); fertilizer kg = deficit ÷ nutrient_fraction ÷ recovery_efficiency (N 0.60, P 0.25, K 0.50).',
            'note' => 'Estimated available nutrient ≈ soil ppm × 2 (plough-layer approximation for per-acre ppm).',
        ];
    }

    /* --------------------------- helpers --------------------------- */

    private function pestProtectionDefault(string $category): int
    {
        return match ($category) {
            'Vegetable' => 15000,
            'Tuber' => 12000,
            'Fruit' => 10000,
            default => 6000,
        };
    }

    private function irrigationDefault($crop): int
    {
        if ((float) $crop->water_requirement_mm >= 800) {
            return 10000;
        }
        if ((float) $crop->water_requirement_mm >= 500) {
            return 5000;
        }

        return 2000;
    }

    private function transportDefault(string $category): int
    {
        return match ($category) {
            'Vegetable' => 8000,
            'Fruit' => 7000,
            'Cash Crop' => 6000,
            default => 4000,
        };
    }

    private function sumVariable(array $costs): int
    {
        return ($costs['seeds'] ?? 0) + ($costs['fertilizer'] ?? 0) + ($costs['cropProtection'] ?? 0)
            + ($costs['irrigation'] ?? 0) + ($costs['labour'] ?? 0) + ($costs['transportPackaging'] ?? 0);
    }

    private function ppmLevel($ppm): ?string
    {
        if ($ppm === null) {
            return null;
        }
        if ($ppm < 20) {
            return 'Low';
        }
        if ($ppm < 50) {
            return 'Medium';
        }

        return 'High';
    }

    private function ppmLevelFor(string $nutrient, $soilTest): ?string
    {
        return match ($nutrient) {
            'n' => $this->ppmLevel($soilTest->n_ppm ?? null),
            'p2o5' => $this->ppmLevel($soilTest->p_ppm ?? null),
            default => $this->ppmLevel($soilTest->k_ppm ?? null),
        };
    }

    private function round1($v)
    {
        return round($v * 10) / 10;
    }

    private function round2($v)
    {
        return round($v * 100) / 100;
    }

    private const CROP_NUTRIENT_DEMAND = [
        'Cereal' => ['n' => 46, 'p2o5' => 23, 'k2o' => 20],
        'Legume' => ['n' => 10, 'p2o5' => 23, 'k2o' => 18],
        'Tuber' => ['n' => 50, 'p2o5' => 40, 'k2o' => 60],
        'Vegetable' => ['n' => 60, 'p2o5' => 30, 'k2o' => 45],
        'Fruit' => ['n' => 30, 'p2o5' => 20, 'k2o' => 35],
        'Oilseed' => ['n' => 30, 'p2o5' => 20, 'k2o' => 20],
        'Fodder' => ['n' => 40, 'p2o5' => 20, 'k2o' => 30],
        'Cash Crop' => ['n' => 45, 'p2o5' => 25, 'k2o' => 30],
    ];

    private const FERTILIZERS = [
        ['name' => 'DAP (18-46-0)', 'n' => 0.18, 'p2o5' => 0.46, 'k2o' => 0],
        ['name' => 'NPK 17:17:17', 'n' => 0.17, 'p2o5' => 0.17, 'k2o' => 0.17],
        ['name' => 'CAN (26% N)', 'n' => 0.26, 'p2o5' => 0, 'k2o' => 0],
        ['name' => 'Urea (46% N)', 'n' => 0.46, 'p2o5' => 0, 'k2o' => 0],
        ['name' => 'MOP (60% K2O)', 'n' => 0, 'p2o5' => 0, 'k2o' => 0.60],
        ['name' => 'SSP (0-20-0)', 'n' => 0, 'p2o5' => 0.20, 'k2o' => 0],
    ];

    private const RECOVERY = ['n' => 0.6, 'p2o5' => 0.25, 'k2o' => 0.5];
}

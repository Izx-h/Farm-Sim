<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\SoilTest;
use App\Services\DecisionEngine;
use PHPUnit\Framework\TestCase;

class DecisionEngineTest extends TestCase
{
    private DecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DecisionEngine();
    }

    private function farm(array $overrides = []): Farm
    {
        return new Farm(array_merge([
            'soil_ph' => 6.2,
            'elevation_m' => 1850,
            'slope_deg' => 3.5,
            'drainage_class' => 'Well-drained',
            'water_source' => 'Borehole & Drip',
            'nitrogen_level' => 'Medium',
            'phosphorus_level' => 'Medium',
            'potassium_level' => 'High',
            'organic_matter_pct' => 3.8,
            'farming_goal' => 'Commercial',
            'area_acres' => 5.0,
            'latitude' => -0.3031,
            'longitude' => 36.08,
            'location_name' => 'Nakuru',
            'groundwater_ec_ds_m' => 1.15,
            'groundwater_tds_mg_l' => 735,
            'groundwater_yield_m3_hr' => 16.5,
            'groundwater_depth_m' => 48,
            'groundwater_salinity_class' => 'Fresh to Mildly Mineralized',
            'groundwater_irrigation_suitability' => 'Class 2 - Suitable with Precautions',
            'aquifer_basin' => 'Nakuru-Naivasha Rift Volcanic Tuff Aquifer',
        ], $overrides));
    }

    private function crop(array $overrides = []): Crop
    {
        return new Crop(array_merge([
            'name' => 'Hybrid Maize (Corn)',
            'category' => 'Cereal',
            'optimal_ph_min' => 6.0,
            'optimal_ph_max' => 7.0,
            'min_ph' => 5.5,
            'max_ph' => 7.5,
            'min_elevation_m' => 800,
            'max_elevation_m' => 2400,
            'drought_tolerance' => 'Moderate',
            'flood_tolerance' => 'Low',
            'salinity_tolerance_ec_ds_m' => 1.7,
            'food_security_rating' => 10,
            'commercial_value_rating' => 8,
            'maturity_days' => 135,
            'average_yield_tons_per_acre' => 3.2,
            'market_price_per_kg' => 60,
            'seed_cost_per_acre' => 7500,
            'fertilizer_cost_per_acre' => 14000,
            'labor_cost_per_acre' => 9500,
            'water_requirement_mm' => 600,
        ], $overrides));
    }

    public function test_suitability_score_is_clamped(): void
    {
        $result = $this->engine->evaluateCropSuitability($this->farm(), $this->crop());

        $this->assertGreaterThanOrEqual(18, $result['suitabilityScore']);
        $this->assertLessThanOrEqual(98, $result['suitabilityScore']);
        $this->assertIsArray($result['strengths']);
        $this->assertIsArray($result['limitingFactors']);
        $this->assertIsArray($result['interventions']);
        $this->assertArrayHasKey('isRecommended', $result);
    }

    public function test_maize_is_recommended_on_a_suitable_farm(): void
    {
        $result = $this->engine->evaluateCropSuitability($this->farm(), $this->crop());

        $this->assertTrue($result['isRecommended']);
        $this->assertGreaterThanOrEqual(65, $result['suitabilityScore']);
    }

    public function test_economics_returns_three_scenarios_and_break_even(): void
    {
        $result = $this->engine->calculateCropEconomics($this->crop(), 5.0);

        $this->assertArrayHasKey('conservative', $result['scenarios']);
        $this->assertArrayHasKey('expected', $result['scenarios']);
        $this->assertArrayHasKey('optimistic', $result['scenarios']);

        $this->assertEquals('KSh', $result['currency']);
        $this->assertArrayHasKey('pricePerKg', $result['breakEven']);
        $this->assertArrayHasKey('yieldTons', $result['breakEven']);

        // Expected yield scales linearly with acreage.
        $this->assertEqualsWithDelta(16.0, $result['scenarios']['expected']['yieldTons'], 0.01);
    }

    public function test_fertilizer_with_lab_data_uses_laboratory_basis(): void
    {
        $soilTest = new SoilTest([
            'n_ppm' => 10,
            'p_ppm' => 8,
            'k_ppm' => 60,
        ]);

        $result = $this->engine->calculateFertilizerRecommendation($this->crop(), $soilTest, 2.0);

        $this->assertEquals('laboratory', $result['dataBasis']);
        $this->assertEmpty($result['missingInputs']);
        $this->assertCount(3, $result['nutrients']);
    }

    public function test_fertilizer_without_lab_data_is_general(): void
    {
        $result = $this->engine->calculateFertilizerRecommendation($this->crop(), null, 1.0);

        $this->assertEquals('general', $result['dataBasis']);
        $this->assertNotEmpty($result['missingInputs']);
    }
}

<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use PHPUnit\Framework\TestCase;
use stdClass;

class WeatherServiceTest extends TestCase
{
    private function weather(array $days): array
    {
        return ['forecastDays' => $days];
    }

    private function day(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-11-08',
            'dayName' => 'Sun',
            'rainMm' => 0,
            'rainProbabilityPct' => null,
            'tempMax' => 26,
            'tempMin' => 14,
            'humidity' => 60,
            'condition' => 'Partly Cloudy',
            'plantingSuitability' => 'Dry / Needs Irrigation',
        ], $overrides);
    }

    private function farm(): stdClass
    {
        $farm = new stdClass();
        $farm->location_name = 'Nakuru';

        return $farm;
    }

    public function test_advisory_flags_insufficient_rainfall(): void
    {
        $service = new WeatherService();
        $advisory = $service->buildPlantingAdvisory(
            $this->weather([$this->day(), $this->day(['date' => '2026-11-09', 'dayName' => 'Mon'])]),
            $this->farm()
        );

        $this->assertEquals('Insufficient Forecast Rainfall', $advisory['seasonalStatus']);
        $this->assertArrayHasKey('advisoryRecommendation', $advisory);
        $this->assertArrayHasKey('riskAlerts', $advisory);
        $this->assertEquals(0, $advisory['derivedMetrics']['rainDays']);
    }

    public function test_advisory_flags_favourable_moisture(): void
    {
        $service = new WeatherService();
        $days = [
            $this->day(['rainMm' => 12]),
            $this->day(['date' => '2026-11-09', 'dayName' => 'Mon', 'rainMm' => 10]),
            $this->day(['date' => '2026-11-10', 'dayName' => 'Tue', 'rainMm' => 9]),
        ];

        $advisory = $service->buildPlantingAdvisory($this->weather($days), $this->farm());

        $this->assertEquals('Sowing Window Favourable', $advisory['seasonalStatus']);
        $this->assertGreaterThanOrEqual(3, $advisory['derivedMetrics']['rainDays']);
        $this->assertGreaterThanOrEqual(30, $advisory['derivedMetrics']['totalRain7dMm']);
    }

    public function test_advisory_reports_risk_alerts_on_heavy_rain(): void
    {
        $service = new WeatherService();
        $advisory = $service->buildPlantingAdvisory(
            $this->weather([$this->day(['rainMm' => 40])]),
            $this->farm()
        );

        $this->assertNotEmpty($advisory['riskAlerts']);
    }
}

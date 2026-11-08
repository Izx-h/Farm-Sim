<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Open-Meteo weather integration with a transparent planting advisory.
 *
 * Port of the original `weatherService.js`. Forecasts are model output (never
 * observed rainfall); the advisory is an explicitly-labelled heuristic derived
 * from the 7-day forecast. Responses are cached for 30 minutes.
 */
class WeatherService
{
    private const CACHE_TTL = 1800; // 30 minutes

    private const WEATHER_CODES = [
        0 => 'Clear Sky', 1 => 'Mainly Clear', 2 => 'Partly Cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Depositing Rime Fog',
        51 => 'Light Drizzle', 53 => 'Moderate Drizzle', 55 => 'Dense Drizzle',
        61 => 'Light Rain', 63 => 'Moderate Rain', 65 => 'Heavy Rain',
        66 => 'Freezing Rain', 67 => 'Heavy Freezing Rain',
        71 => 'Light Snow', 73 => 'Moderate Snow', 75 => 'Heavy Snow',
        80 => 'Light Showers', 81 => 'Moderate Showers', 82 => 'Violent Showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm w/ Hail', 99 => 'Thunderstorm w/ Heavy Hail',
    ];

    public function getWeather($lat, $lon): array
    {
        $latitude = (float) $lat;
        $longitude = (float) $lon;

        if (! is_finite($latitude) || ! is_finite($longitude)) {
            return ['dataStatus' => 'unavailable', 'error' => 'Invalid coordinates', 'latitude' => $latitude, 'longitude' => $longitude];
        }

        $cacheKey = sprintf('weather:%s,%s', round($latitude, 2), round($longitude, 2));

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey) + ['dataStatus' => 'cached'];
        }

        $base = config('services.open_meteo.base_url', 'https://api.open-meteo.com/v1');

        try {
            $res = Http::timeout(8)->get("{$base}/forecast", [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,precipitation',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,relative_humidity_2m_max',
                'timezone' => 'auto',
                'forecast_days' => 7,
            ]);

            if (! $res->ok()) {
                throw new \RuntimeException("Open-Meteo responded {$res->status()}");
            }

            $normalized = $this->normalize($res->json(), $latitude, $longitude);
            Cache::put($cacheKey, $normalized, self::CACHE_TTL);

            return $normalized + ['dataStatus' => 'ok'];
        } catch (\Throwable $e) {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey) + ['dataStatus' => 'stale', 'error' => $e->getMessage()];
            }

            return ['dataStatus' => 'unavailable', 'error' => $e->getMessage(), 'latitude' => $latitude, 'longitude' => $longitude];
        }
    }

    private function normalize(array $data, $latitude, $longitude): array
    {
        if (empty($data['daily']['time'])) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'dataStatus' => 'unavailable',
                'error' => 'No forecast data returned',
            ];
        }

        $current = $data['current'] ?? [];
        $daily = $data['daily'];
        $times = $daily['time'] ?? [];

        $forecastDays = [];
        foreach ($times as $i => $date) {
            $rainMm = $this->num($daily['precipitation_sum'][$i] ?? null);
            $rainProb = $this->num($daily['precipitation_probability_max'][$i] ?? null);
            $tempMax = $this->num($daily['temperature_2m_max'][$i] ?? null);
            $tempMin = $this->num($daily['temperature_2m_min'][$i] ?? null);
            $humidity = $this->num($daily['relative_humidity_2m_max'][$i] ?? null);
            $code = $daily['weather_code'][$i] ?? null;

            $forecastDays[] = [
                'date' => $date,
                'dayName' => $this->dayName($date),
                'rainMm' => $rainMm,
                'rainProbabilityPct' => $rainProb,
                'tempMax' => $tempMax,
                'tempMin' => $tempMin,
                'humidity' => $humidity,
                'condition' => $this->codeToLabel($code),
                'plantingSuitability' => $this->plantingSuitabilityForDay($rainMm, $rainProb, $tempMin, $tempMax),
            ];
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $data['timezone'] ?? 'auto',
            'source' => 'Open-Meteo',
            'sourceUrl' => 'https://open-meteo.com/',
            'retrievedAt' => Carbon::now()->toIso8601String(),
            'forecastHorizon' => '7 days',
            'current' => [
                'tempC' => $this->num($current['temperature_2m'] ?? null),
                'humidityPct' => $this->num($current['relative_humidity_2m'] ?? null),
                'condition' => $this->codeToLabel($current['weather_code'] ?? null),
                'precipitationMm' => $this->num($current['precipitation'] ?? null),
                'classification' => 'model-estimate',
            ],
            'forecastDays' => $forecastDays,
            'units' => [
                'temperature' => '°C',
                'precipitation' => 'mm',
                'humidity' => '%',
                'probability' => '%',
            ],
        ];
    }

    public function buildPlantingAdvisory(array $weather, $farm): array
    {
        $days = $weather['forecastDays'] ?? [];
        $today = $days[0] ?? null;
        $week = array_slice($days, 0, 7);

        $totalRain = array_reduce($week, fn ($s, $d) => $s + ($d['rainMm'] ?? 0), 0);
        $rainDays = count(array_filter($week, fn ($d) => ($d['rainMm'] ?? 0) >= 2));
        $avgMax = $this->avg(array_map(fn ($d) => $d['tempMax'], $week));
        $avgMin = $this->avg(array_map(fn ($d) => $d['tempMin'], $week));

        $month = (int) Carbon::now()->format('n');
        if ($month >= 3 && $month <= 5) {
            $seasonName = 'Long Rains Season';
        } elseif ($month >= 6 && $month <= 9) {
            $seasonName = 'Cool Dry Season';
        } elseif ($month <= 2) {
            $seasonName = 'Warm Dry Period';
        } else {
            $seasonName = 'Short Rains Season';
        }

        $adequateMoisture = $totalRain >= 30 || $rainDays >= 3;
        $hotStress = $avgMax !== null && $avgMax >= 33;
        $coldStress = $avgMin !== null && $avgMin < 10;

        if ($adequateMoisture) {
            $seasonalStatus = 'Sowing Window Favourable';
            $advisory = "The 7-day forecast shows ~{$this->round($totalRain)} mm cumulative rainfall across {$rainDays} rain day(s) at ".($farm->location_name ?? 'this location').'. Conditions are forecast to be sufficiently moist for germination, provided this rain actually falls.';
        } else {
            $seasonalStatus = 'Insufficient Forecast Rainfall';
            $advisory = "The 7-day forecast shows only ~{$this->round($totalRain)} mm cumulative rainfall ({$rainDays} rain day(s)). This is below the ~30 mm needed for reliable dry-land germination. Consider waiting for a stronger onset or using irrigation.";
        }

        $risks = [];
        if ($hotStress) {
            $risks[] = "Forecast daytime highs reach ~{$this->round($avgMax)}°C — heat stress and high evaporation are likely around sowing.";
        }
        if ($coldStress) {
            $risks[] = "Forecast night lows near {$this->round($avgMin)}°C — cold stress can slow germination for warm-season crops.";
        }
        if ($today && ($today['rainMm'] ?? 0) > 25) {
            $risks[] = "Heavy rain ({$this->round($today['rainMm'])} mm) is forecast today — avoid sowing into saturated soil (waterlogging, mud).";
        }
        if ($rainDays === 0) {
            $risks[] = 'No meaningful rain is forecast in the next 7 days — rain-fed sowing is not advised.';
        }

        return [
            'seasonName' => $seasonName,
            'seasonalStatus' => $seasonalStatus,
            'advisoryRecommendation' => $advisory,
            'riskAlerts' => $risks,
            'derivedMetrics' => [
                'totalRain7dMm' => $this->round($totalRain),
                'rainDays' => $rainDays,
                'avgMaxTempC' => $this->round($avgMax),
                'avgMinTempC' => $this->round($avgMin),
                'note' => 'Values are computed by AgriPulse from the Open-Meteo 7-day forecast. They are estimates, not observed rainfall, and not a guarantee of onset.',
            ],
        ];
    }

    private function plantingSuitabilityForDay($rainMm, $rainProb, $tempMin, $tempMax): string
    {
        if (! is_finite($rainMm)) {
            return 'Unknown';
        }
        if ($rainMm > 25) {
            return 'Delay (Heavy Rain / Waterlogging)';
        }
        if ($tempMax !== null && $tempMin !== null && $tempMax > 34) {
            return 'Heat Stress Risk';
        }
        if ($rainMm >= 2 && $rainMm <= 25) {
            return 'Favourable Moisture';
        }
        if ($rainProb !== null && $rainProb >= 40) {
            return 'Rain Likely';
        }

        return 'Dry / Needs Irrigation';
    }

    private function codeToLabel($code): string
    {
        return self::WEATHER_CODES[$code] ?? 'Unknown';
    }

    private function num($v)
    {
        if ($v === null) {
            return null;
        }
        $n = (float) $v;

        return is_finite($n) ? round($n * 10) / 10 : null;
    }

    private function dayName(string $date): string
    {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(int) date('w', strtotime($date))];
    }

    private function avg(array $arr)
    {
        $vals = array_values(array_filter($arr, fn ($v) => is_finite($v)));
        if (count($vals) === 0) {
            return null;
        }

        return array_sum($vals) / count($vals);
    }

    private function round($v)
    {
        return $v === null ? null : round($v * 10) / 10;
    }
}

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\EconomicsController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FertilizerController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\GroundwaterController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PestController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\WeatherController;
use App\Http\Middleware\AuthenticateToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — AgriPulse
|--------------------------------------------------------------------------
|
| Mirrors the original Express API surface (1:1), re-expressed as Laravel
| routes. Read-only reference endpoints are public; everything scoped to a
| user (farms, plans, weather, fertilizer, consultations) requires a bearer
| token via the AuthenticateToken middleware.
|
*/

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'AgriPulse API']));

/* ------------------------- Authentication ------------------------- */
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

/* ------------------------- Public reference data ------------------------- */
Route::get('/geo/lookup', [GeoController::class, 'lookup']);
Route::get('/groundwater/stations', [GroundwaterController::class, 'stations']);
Route::get('/groundwater/lookup', [GroundwaterController::class, 'lookup']);

Route::get('/crops', [CropController::class, 'index']);
Route::get('/crops/{id}', [CropController::class, 'show']);

Route::get('/pests', [PestController::class, 'index']);
Route::post('/pests/diagnose', [PestController::class, 'diagnose']);

Route::get('/markets', [MarketController::class, 'index']);
Route::post('/economics/simulate', [EconomicsController::class, 'simulate']);

Route::get('/specialists', [SpecialistController::class, 'index']);

/* ------------------------- Authenticated routes ------------------------- */
Route::middleware(AuthenticateToken::class)->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/onboarding', [AuthController::class, 'onboarding']);

    // Farms & farm profiling
    Route::get('/farms', [FarmController::class, 'index']);
    Route::post('/farms', [FarmController::class, 'store']);
    Route::get('/farms/{id}', [FarmController::class, 'show']);
    Route::put('/farms/{id}', [FarmController::class, 'update']);
    Route::delete('/farms/{id}', [FarmController::class, 'destroy']);
    Route::post('/farms/{id}/soil-tests', [FarmController::class, 'storeSoilTest']);
    Route::post('/farms/{id}/water-tests', [FarmController::class, 'storeWaterTest']);

    // Crop suitability, weather, fertilizer (per farm)
    Route::get('/farms/{id}/suitability', [CropController::class, 'suitability']);
    Route::get('/farms/{id}/weather', [WeatherController::class, 'show']);
    Route::get('/farms/{id}/fertilizer', [FertilizerController::class, 'show']);

    // Crop management plans & tasks
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{id}', [PlanController::class, 'show']);
    Route::post('/plans/generate', [PlanController::class, 'generate']);
    Route::put('/tasks/{id}', [PlanController::class, 'updateTask']);

    // Manual market price import & specialist consultations
    Route::post('/markets/import', [MarketController::class, 'import']);
    Route::post('/specialists/consult', [SpecialistController::class, 'consult']);
});

<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmPlan;
use App\Models\PlanTask;
use App\Services\DecisionEngine;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private DecisionEngine $engine)
    {
    }

    public function index(Request $request)
    {
        $plans = FarmPlan::query()
            ->join('farms', 'farms.id', '=', 'farm_plans.farm_id')
            ->join('crops', 'crops.id', '=', 'farm_plans.crop_id')
            ->where('farms.user_id', $request->user()->id)
            ->select(
                'farm_plans.*',
                'farms.name as farm_name',
                'farms.location_name',
                'crops.name as crop_name',
                'crops.category as crop_category'
            )
            ->orderByDesc('farm_plans.created_at')
            ->get()
            ->map(function ($plan) {
                return [...$plan->toArray(), 'tasks' => PlanTask::where('plan_id', $plan->id)->orderBy('due_day_offset')->get()];
            });

        return response()->json($plans);
    }

    public function show(Request $request, string $id)
    {
        $plan = FarmPlan::query()
            ->join('farms', 'farms.id', '=', 'farm_plans.farm_id')
            ->join('crops', 'crops.id', '=', 'farm_plans.crop_id')
            ->where('farm_plans.id', $id)
            ->select(
                'farm_plans.*',
                'farms.name as farm_name',
                'farms.location_name',
                'farms.soil_ph',
                'farms.water_source',
                'farms.user_id as owner_user_id',
                'crops.name as crop_name',
                'crops.scientific_name',
                'crops.category as crop_category',
                'crops.maturity_days',
                'crops.spacing_recommendation',
                'crops.planting_depth_cm'
            )
            ->first();

        if (! $plan || $plan->owner_user_id !== $request->user()->id) {
            return response()->json(['error' => 'Plan not found'], 404);
        }

        return response()->json([
            ...$plan->toArray(),
            'tasks' => PlanTask::where('plan_id', $plan->id)->orderBy('due_day_offset')->get(),
            'stages' => \App\Models\CropGrowthStage::where('crop_id', $plan->crop_id)->orderBy('stage_order')->get(),
        ]);
    }

    public function generate(Request $request)
    {
        $farm = Farm::where('id', $request->input('farm_id'))->where('user_id', $request->user()->id)->first();
        $crop = Crop::find($request->input('crop_id'));
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }
        if (! $crop) {
            return response()->json(['error' => 'Crop not found'], 404);
        }

        $acres = (float) $request->input('planted_area_acres') ?: (float) $farm->area_acres ?: 1;
        $plantingDate = $request->input('target_planting_date')
            ? new \DateTimeImmutable($request->input('target_planting_date'))
            : new \DateTimeImmutable();
        $harvestDate = $plantingDate->modify("+{$crop->maturity_days} days");

        $economics = $this->engine->calculateCropEconomics($crop, $acres);

        $plan = FarmPlan::create([
            'farm_id' => $farm->id,
            'crop_id' => $crop->id,
            'season_name' => $request->input('season_name', 'Season 2026/2027'),
            'target_planting_date' => $plantingDate->format('Y-m-d'),
            'estimated_harvest_date' => $harvestDate->format('Y-m-d'),
            'planted_area_acres' => $acres,
            'status' => 'Active',
            'projected_yield_tons' => $economics['scenarios']['expected']['yieldTons'],
            'projected_revenue' => $economics['scenarios']['expected']['revenue'],
            'estimated_total_cost' => $economics['costs']['totalCost'],
            'projected_net_margin' => $economics['scenarios']['expected']['netProfit'],
            'notes' => "Auto-generated plan for {$crop->name} tailored to {$farm->soil_type} and {$farm->water_source}.",
            'variety' => $request->input('variety', $crop->variety ?? ''),
        ]);

        foreach ($this->buildLifecycleTasks($crop) as $t) {
            PlanTask::create([
                'plan_id' => $plan->id,
                'title' => $t['title'],
                'category' => $t['category'],
                'growth_stage' => $t['stage'],
                'due_day_offset' => $t['offset'],
                'status' => 'Pending',
                'notes' => $t['notes'],
            ]);
        }

        return response()->json([
            ...$plan->fresh()->toArray(),
            'tasks' => PlanTask::where('plan_id', $plan->id)->orderBy('due_day_offset')->get(),
        ], 201);
    }

    public function updateTask(Request $request, string $id)
    {
        $status = $request->input('status');
        $notes = $request->input('notes');
        $completedAt = $status === 'Completed' ? now() : null;

        $task = PlanTask::query()
            ->join('farm_plans', 'farm_plans.id', '=', 'plan_tasks.plan_id')
            ->join('farms', 'farms.id', '=', 'farm_plans.farm_id')
            ->where('farms.user_id', $request->user()->id)
            ->where('plan_tasks.id', $id)
            ->select('plan_tasks.*')
            ->first();

        if (! $task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task->update([
            'status' => $status,
            'notes' => $notes ?? $task->notes,
            'completed_at' => $completedAt ?? $task->completed_at,
        ]);

        return response()->json($task);
    }

    private function buildLifecycleTasks($crop): array
    {
        $m = (int) $crop->maturity_days ?: 120;
        $p = fn ($f) => (int) round($m * $f);
        $spacing = $crop->spacing_recommendation ?: 'as per recommendation';
        $depth = $crop->planting_depth_cm ?: 3;

        $varietyHint = $crop->variety ? ' (e.g. '.explode(',', $crop->variety)[0].')' : '';

        return [
            ['title' => 'Land Preparation & Soil Sampling', 'category' => 'Land Prep', 'stage' => 'Pre-Planting', 'offset' => -21, 'notes' => 'Plough/harrow and collect a soil sample for pH and nutrient analysis before any input purchase.'],
            ['title' => 'Soil Amendment & Liming (if needed)', 'category' => 'Soil Amendment', 'stage' => 'Pre-Planting', 'offset' => -14, 'notes' => 'Apply lime/gypsum or compost based on soil-test results to correct pH and structure.'],
            ['title' => 'Seed / Planting Material Procurement', 'category' => 'Planting Material', 'stage' => 'Pre-Planting', 'offset' => -7, 'notes' => "Procure certified seed{$varietyHint} and basal fertilizer."],
            ['title' => "Sowing / Planting (spacing {$spacing})", 'category' => 'Planting', 'stage' => 'Sowing', 'offset' => 0, 'notes' => "Plant at {$depth} cm depth when soil moisture is adequate."],
            ['title' => 'Early Vegetative Growth & First Weeding', 'category' => 'Weeding', 'stage' => 'Early Vegetative', 'offset' => $p(0.15), 'notes' => 'Remove weeds while small; scout for seedling pests and gaps for gapping.'],
            ['title' => 'Vegetative Growth & Nutrient Management (Top-dress)', 'category' => 'Fertilization', 'stage' => 'Vegetative', 'offset' => $p(0.28), 'notes' => 'Apply nitrogenous top-dressing into moist soil; split where indicated.'],
            ['title' => 'Flowering / Reproductive Development', 'category' => 'Pest Control', 'stage' => 'Flowering', 'offset' => $p(0.5), 'notes' => 'Ensure uninterrupted moisture; monitor for blights, borers and insect vectors.'],
            ['title' => 'Fruit / Grain / Tuber Development', 'category' => 'Irrigation', 'stage' => 'Development', 'offset' => $p(0.68), 'notes' => 'Maintain moisture to support fill/bulking; scout for storage pests.'],
            ['title' => 'Harvest Preparation & Harvesting', 'category' => 'Harvesting', 'stage' => 'Ripening', 'offset' => $m, 'notes' => 'Confirm physiological maturity; harvest in dry weather.'],
            ['title' => 'Post-Harvest Handling & Storage', 'category' => 'Post-Harvest', 'stage' => 'Post-Harvest', 'offset' => $m + 7, 'notes' => 'Dry, clean, grade and store to minimize losses and aflatoxin.'],
        ];
    }
}

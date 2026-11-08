<?php

namespace App\Http\Controllers;

use App\Models\PestAlert;
use App\Models\PestDisease;
use Illuminate\Http\Request;

class PestController extends Controller
{
    public function index()
    {
        return response()->json([
            'pests' => PestDisease::all(),
            'alerts' => PestAlert::query()
                ->join('pest_diseases', 'pest_diseases.id', '=', 'pest_alerts.pest_disease_id')
                ->select(
                    'pest_alerts.*',
                    'pest_diseases.name as pest_name',
                    'pest_diseases.type as pest_type',
                    'pest_diseases.severity as pest_severity'
                )
                ->orderByDesc('pest_alerts.reported_date')
                ->get(),
        ]);
    }

    public function diagnose(Request $request)
    {
        $cropName = strtolower((string) $request->input('cropName', ''));
        $plantPart = strtolower((string) $request->input('plantPart', ''));
        $observedSymptoms = strtolower((string) $request->input('observedSymptoms', ''));
        $weatherCondition = strtolower((string) $request->input('weatherCondition', ''));

        $pests = PestDisease::all()->map(function ($p) use ($cropName, $plantPart, $observedSymptoms) {
            $haystack = strtolower("{$p->name} {$p->affected_crops} {$p->symptoms} {$p->favorable_conditions}");

            $score = 0;
            if ($cropName && str_contains($haystack, $cropName)) {
                $score += 40;
            }
            if ($plantPart && str_contains(strtolower($p->symptoms), $plantPart)) {
                $score += 15;
            }
            foreach (preg_split('/\s+/', $observedSymptoms) ?: [] as $word) {
                if (strlen($word) > 3 && str_contains($haystack, $word)) {
                    $score += 15;
                }
            }

            return [...$p->toArray(), 'confidenceScore' => min(92, max(20, $score))];
        })
            ->sortByDesc('confidenceScore')
            ->values();

        return response()->json([
            'query' => ['cropName' => $request->input('cropName'), 'plantPart' => $request->input('plantPart'), 'observedSymptoms' => $request->input('observedSymptoms')],
            'topDiagnosis' => $pests->first(),
            'possibleCandidates' => $pests->slice(0, 3)->values(),
            'disclaimer' => 'Diagnoses are probabilistic keyword matches from the built-in pest reference library — not a confirmed laboratory identification. Confirm severe cases with an extension officer or plant pathologist.',
        ]);
    }
}

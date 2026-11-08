<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Farm;
use App\Models\Specialist;
use Illuminate\Http\Request;

class SpecialistController extends Controller
{
    public function index()
    {
        return response()->json(Specialist::orderByDesc('rating')->get());
    }

    public function consult(Request $request)
    {
        $farm = Farm::where('id', $request->input('farm_id'))->where('user_id', $request->user()->id)->first();
        if (! $farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $specialist = Specialist::find($request->input('specialist_id'));
        if (! $specialist) {
            return response()->json(['error' => 'Specialist not found'], 404);
        }

        $consultation = Consultation::create([
            'farm_id' => $farm->id,
            'specialist_id' => $specialist->id,
            'farmer_name' => $request->user()->name,
            'inquiry_type' => $request->input('inquiry_type'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'status' => 'Open',
        ]);

        return response()->json([
            'id' => $consultation->id,
            'status' => 'Open',
            'specialistName' => $specialist->name,
            'message' => 'Consultation request successfully submitted.',
        ], 201);
    }
}

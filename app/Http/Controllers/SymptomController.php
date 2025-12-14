<?php

namespace App\Http\Controllers;

use App\Models\Symptom;
use App\Http\Controllers\Controller;

class SymptomController extends Controller
{
    // GET /api/symptoms
    public function index()
    {
        $symptoms = Symptom::with('category')
            ->orderBy('category_id')
            ->orderBy('symptom_name')
            ->get()
            ->map(function ($symptom) {
                return [
                    'id' => $symptom->id,
                    'symptom_name' => $symptom->symptom_name,
                    'category' => [
                        'id' => $symptom->category->id,
                        'category_name' => $symptom->category->category_name,
                    ],
                ];
            });

        return response()->json($symptoms);
    }
}
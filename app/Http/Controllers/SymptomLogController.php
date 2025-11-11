<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\SymptomLog;
use App\Http\Controllers\Controller;
use App\Services\RecommendationService;

class SymptomLogController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function storeLog(Request $request) {
        $validated = $request->validate([
            'log_date' => 'required|date',
            'symptom_ids' => 'nullable|array',
            'symptom_ids.*' => 'exists:symptoms,id',
        ]);

        $user = $request->user();
        

        DB::transaction(function() use ($validated, $user) {
            SymptomLog::where('user_id', $user->id)
                ->where('log_date', $validated['log_date'])
                ->delete();
            
            if (!empty($validated['symptom_ids'])) {
                foreach ($validated['symptom_ids'] as $symptomId) {
                    SymptomLog::create([
                        'user_id' => $user->id,
                        'symptom_id' => $symptomId,
                        'log_date' => $validated['log_date'],
                    ]);
        
                }
            }
        });

        $this->recommendationService->clearCache($user->id);
        $recommendations = $this->recommendationService->generateRecommendations($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Log saved successfully',
            // 'recommendations' => $recommendations,
        ]);
    }

    // lihat symptom log berdasarkan tanggal
    public function show(Request $request) {
        $request->validate([
            'log_date' => 'required|date',
        ]);

        $logs = SymptomLog::with('symptom') // pakai relasi yang ada
            ->where('user_id', $request->user()->id)
            ->whereDate('log_date', $request->query('log_date'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }



    public function getSymptomHistory(Request $request)
    {
        $user = $request->user();
        
        // Ambil data 6 bulan terakhir
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        
        $symptoms = SymptomLog::with(['symptom.category']) // Nested relationship!
            ->where('user_id', $user->id)
            ->where('log_date', '>=', $sixMonthsAgo)
            ->orderBy('log_date', 'desc')
            ->get();

        if ($symptoms->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada riwayat gejala',
                'data' => [],
            ]);
        }

        // Group by bulan
        $history = $symptoms->groupBy(function($symptom) {
            return Carbon::parse($symptom->log_date)->locale('id')->isoFormat('MMMM YYYY');
        })->map(function($monthSymptoms, $month) {
            // Ambil unique symptom names per bulan
            $uniqueSymptoms = $monthSymptoms
                ->map(function($log) {
                    // Ambil nama symptom dari nested relationship
                    return $log->symptom ? $log->symptom->symptom_name : null;
                })
                ->filter() // Remove null
                ->unique()
                ->values()
                ->all();

            return [
                'month' => $month,
                'symptoms' => $uniqueSymptoms,
                'total_symptoms' => count($uniqueSymptoms),
            ];
        })->values()->all();

        return response()->json([
            'message' => 'Riwayat gejala berhasil diambil',
            'data' => $history,
        ]);
    }
}

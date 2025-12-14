<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\SymptomLog;
use App\Models\Symptom;
use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Log;

class SymptomLogController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function getSymptoms()
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

    public function storeLog(Request $request)
    {
        $validated = $request->validate([
            'log_date' => 'required|date|before_or_equal:today',
            'symptom_ids' => 'nullable|array',
            'symptom_ids.*' => 'exists:symptoms,id',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($validated, $user) {
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
        ]);
    }

    public function show(Request $request)
    {
        $request->validate([
            'log_date' => 'required|date|before_or_equal:today',
        ]);

        $logs = SymptomLog::with('symptom')
            ->where('user_id', $request->user()->id)
            ->whereDate('log_date', $request->query('log_date'))
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'symptom_id' => $log->symptom_id,
                    'symptom_name' => $log->symptom->symptom_name,
                    'category_name' => $log->symptom->category->category_name,
                    'category_id' => $log->symptom->category->id,
                    'log_date' => $log->log_date,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    // GET /api/symptom-logs/calendar?month=YYYY-MM
    public function getCalendarSymptoms(Request $request)
    {
        try {
            $validated = $request->validate([
                'month' => 'required|date_format:Y-m',
            ]);

            $user = $request->user();
            $month = $validated['month'];

            Log::info("=== getCalendarSymptoms START ===");
            Log::info("User ID: {$user->id}");
            Log::info("Month: {$month}");

            $firstDay = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $lastDay = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            Log::info("Date range: {$firstDay} to {$lastDay}");

            $symptomLogs = SymptomLog::with('symptom.category')
                ->where('user_id', $user->id)
                ->whereDate('log_date', '>=', $firstDay)
                ->whereDate('log_date', '<=', $lastDay)
                ->orderBy('log_date', 'desc')
                ->get();

            Log::info("Found logs count: " . $symptomLogs->count());

            $result = $symptomLogs->groupBy(function ($log) {
                // Jika log_date adalah string, convert ke Carbon
                $date = is_string($log->log_date) 
                    ? Carbon::parse($log->log_date) 
                    : $log->log_date;
                
                return $date->format('Y-m-d');
            })
            ->map(function ($dayLogs, $date) {
                return [
                    'date' => $date,
                    'symptoms' => $dayLogs->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'symptom_id' => $log->symptom_id,
                            'symptom_name' => $log->symptom->symptom_name,
                            'category_name' => $log->symptom->category->category_name,
                            'category_id' => $log->symptom->category->id,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

            Log::info("=== getCalendarSymptoms SUCCESS ===");

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("=== getCalendarSymptoms ERROR ===");
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }


    public function getSymptomHistory(Request $request)
    {
        $user = $request->user();
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $symptoms = SymptomLog::with(['symptom.category'])
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

        $history = $symptoms->groupBy(function ($symptom) {
            return Carbon::parse($symptom->log_date)->locale('id')->isoFormat('MMMM YYYY');
        })->map(function ($monthSymptoms, $month) {
            $uniqueSymptoms = $monthSymptoms
                ->map(function ($log) {
                    return [
                        'symptom_name' => $log->symptom ? $log->symptom->symptom_name : null,
                        'category_name' => $log->symptom ? $log->symptom->category->category_name : null,
                    ];
                })
                ->filter(fn ($item) => $item['symptom_name'] !== null)
                ->unique(fn ($item) => $item['symptom_name'])
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
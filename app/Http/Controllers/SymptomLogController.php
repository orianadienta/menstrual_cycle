<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\SymptomLog;
use App\Models\Symptom;
use App\Services\RecommendationService;

class SymptomLogController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /* =====================================================
     | GET ALL SYMPTOMS
     ===================================================== */
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

    /* =====================================================
     | GET SYMPTOM LOG FOR SPECIFIC DATE
     | GET /api/symptom-logs/show?log_date=YYYY-MM-DD
     ===================================================== */
    public function show(Request $request)
    {
        try {
            $validated = $request->validate([
                'log_date' => 'required|date',
            ]);

            $user = $request->user();
            $logDate = $validated['log_date'];

            $symptomLogs = SymptomLog::with('symptom.category')
                ->where('user_id', $user->id)
                ->where('log_date', $logDate)
                ->get();

            $result = $symptomLogs->map(function ($log) {
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
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in show symptom log: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /* =====================================================
     | STORE / UPDATE DAILY LOG (SAFE MERGE)
     ===================================================== */
    public function storeLog(Request $request)
    {
        $validated = $request->validate([
            'log_date' => 'required|date|before_or_equal:today',
            'symptom_ids' => 'nullable|array',
            'symptom_ids.*' => 'exists:symptoms,id',
        ]);

        $user = $request->user();
        $newIds = $validated['symptom_ids'] ?? [];

        /* ---------- VALIDATION (BEFORE DB) ---------- */
        if (!empty($newIds)) {
            $symptoms = Symptom::with('category')
                ->whereIn('id', $newIds)
                ->get();

            $categoryCount = [];
            $painSymptoms = [];

            foreach ($symptoms as $symptom) {
                $category = $symptom->category->category_name;
                $categoryCount[$category] = ($categoryCount[$category] ?? 0) + 1;

                if ($category === 'Pain') {
                    $painSymptoms[] = $symptom->symptom_name;
                }
            }

            foreach (['Mood', 'Sleep Quality'] as $cat) {
                if (($categoryCount[$cat] ?? 0) > 1) {
                    return response()->json([
                        'success' => false,
                        'message' => "You can only select one {$cat} symptom",
                    ], 422);
                }
            }

            if (in_array('No pain', $painSymptoms) && count($painSymptoms) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot select "No pain" with other pain symptoms',
                ], 422);
            }
        }

        /* ---------- SAFE MERGE UPDATE ---------- */
        DB::transaction(function () use ($user, $validated, $newIds) {

            $existingIds = SymptomLog::where('user_id', $user->id)
                ->where('log_date', $validated['log_date'])
                ->pluck('symptom_id')
                ->toArray();

            // Gabungkan data lama + baru
            $finalIds = array_unique(array_merge($existingIds, $newIds));

            // Hapus hanya yang benar-benar tidak ada
            SymptomLog::where('user_id', $user->id)
                ->where('log_date', $validated['log_date'])
                ->whereNotIn('symptom_id', $finalIds)
                ->delete();

            // Insert yang belum ada
            foreach (array_diff($finalIds, $existingIds) as $id) {
                SymptomLog::create([
                    'user_id' => $user->id,
                    'symptom_id' => $id,
                    'log_date' => $validated['log_date'],
                ]);
            }
        });

        $this->recommendationService->clearCache($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Log saved successfully',
        ]);
    }

    /* =====================================================
     | CALENDAR VIEW
     | GET /api/symptom-logs/calendar?month=YYYY-MM
     ===================================================== */
    public function getCalendarSymptoms(Request $request)
    {
        try {
            $validated = $request->validate([
                'month' => 'required|date_format:Y-m',
            ]);

            $user = $request->user();
            $month = $validated['month'];

            $firstDay = Carbon::createFromFormat('Y-m', $month)
                ->startOfMonth()
                ->toDateString();

            $lastDay = Carbon::createFromFormat('Y-m', $month)
                ->endOfMonth()
                ->toDateString();

            $symptomLogs = SymptomLog::with('symptom.category')
                ->where('user_id', $user->id)
                ->whereBetween('log_date', [$firstDay, $lastDay])
                ->orderBy('log_date', 'desc')
                ->get();

            $result = $symptomLogs
                ->groupBy(fn ($log) => Carbon::parse($log->log_date)->format('Y-m-d'))
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
                        })->values(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /* =====================================================
     | HISTORY (LAST 6 MONTHS)
     ===================================================== */
    public function getSymptomHistory(Request $request)
    {
        $user = $request->user();
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $symptoms = SymptomLog::with('symptom.category')
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

        $history = $symptoms
            ->groupBy(fn ($log) =>
                Carbon::parse($log->log_date)->locale('id')->isoFormat('MMMM YYYY')
            )
            ->map(function ($monthSymptoms, $month) {
                $unique = $monthSymptoms
                    ->map(function ($log) {
                        return [
                            'symptom_name' => $log->symptom->symptom_name,
                            'category_name' => $log->symptom->category->category_name,
                        ];
                    })
                    ->unique('symptom_name')
                    ->values();

                return [
                    'month' => $month,
                    'symptoms' => $unique,
                    'total_symptoms' => $unique->count(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Riwayat gejala berhasil diambil',
            'data' => $history,
        ]);
    }
}
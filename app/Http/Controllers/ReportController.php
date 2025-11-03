<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CycleReportService;

class ReportController extends Controller
{
    /**
     * Get laporan statistik siklus menstruasi
     */
    public function getCycleReport(Request $request)
    {
        $user = $request->user();
        
        // Instantiate service manually (bypass DI issue)
        $reportService = new CycleReportService();
        $report = $reportService->generateReport($user->id);

        if ($report['status'] === 'insufficient_data') {
            return response()->json([
                'message' => $report['message'],
                'data' => $report,
            ], 400);
        }

        return response()->json([
            'message' => 'Laporan siklus berhasil dibuat',
            'data' => $report,
        ]);
    }
}

// public function report(Request $request)
    // {
    //     $user = $request->user();
    //     $startDate = now()->subMonths(3)->startOfMonth();
    //     $endDate = now()->endOfMonth();

    //     $logs = SymptomLog::with('symptom.category')
    //         ->where('user_id', $user->id)
    //         ->whereBetween('log_date', [$startDate, $endDate])
    //         ->get();

    //     // ringkasan per kategori
    //     $summary = $logs->groupBy(fn($log) => $log->symptom->category->name)
    //         ->map(fn($group) => $group->groupBy('symptom.symptom_name')->map->count());

    //     return response()->json([
    //         'success' => true,
    //         'periode' => [
    //             'start' => $startDate->toDateString(),
    //             'end' => $endDate->toDateString(),
    //         ],
    //         'summary' => $summary
    //     ]);

    // ->whereBetween('log_date', [now()->subMonths(3), now()])

    // }
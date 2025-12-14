<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CycleReportService;

class ReportController extends Controller
{
    /**
     * Get laporan statistik siklus menstruasi
     * 
     * @deprecated Gunakan getDashboardReport() sebagai gantinya
     */
    public function getCycleReport(Request $request)
    {
        $user = $request->user();
        $reportService = new CycleReportService();
        $report = $reportService->generateReport($user->id);

        if ($report['status'] !== 'success') {
            return response()->json([
                'status' => $report['status'],
                'message' => $report['message'] ?? 'Gagal membuat laporan',
                'data' => $report,
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan siklus berhasil dibuat',
            'data' => $report,
        ]);
    }

    public function getDashboardReport(Request $request)
    {
        $user = $request->user();
        $reportService = new CycleReportService();
        $report = $reportService->generateDashboardReport($user->id);

        if ($report['status'] !== 'success') {
            return response()->json([
                'status' => $report['status'],
                'message' => $report['message'] ?? 'Gagal membuat laporan',
                'data' => $report,
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dashboard report berhasil dibuat',
            'data' => $report,
        ]);
    }
}
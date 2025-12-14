<?php
namespace App\Services;

use App\Models\Cycle;
use App\Models\TrackingStatus;
use Illuminate\Support\Carbon;

class CycleReportService
{
    // FIGO 2018 Standards
    const FIGO_CYCLE_MIN = 24;
    const FIGO_CYCLE_MAX = 38;
    const FIGO_VARIATION_MAX = 9;
    const FIGO_DURATION_MAX = 8;


    public function generateReport($userId)
    {
        $trackingStatus = TrackingStatus::where('user_id', $userId)
            ->latest()
            ->first();

        $query = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc');
        
        // If tracking was resumed, only get cycles after resume
        if ($trackingStatus && $trackingStatus->resumed_at) {
            $query->where('start_date', '>=', $trackingStatus->resumed_at);
            
            $allCycles = $query->get();
            
            if ($allCycles->count() < 3) {
                return [
                    'status' => 'insufficient_data_after_resume',
                    'message' => 'Minimum 3 new cycles required after resuming tracking',
                    'available_cycles' => $allCycles->count(),
                    'required_cycles' => 3,
                    'resumed_at' => $trackingStatus->resumed_at->format('Y-m-d'),
                ];
            }
        } 
        else {
            // Skip cycles during paused period
            if ($trackingStatus && $trackingStatus->status === 'paused' && $trackingStatus->paused_at) {
                $query->where('start_date', '<', $trackingStatus->paused_at);
            }
            
            $allCycles = $query->get();
            
            if ($allCycles->count() < 3) {
                return [
                    'status' => 'insufficient_data',
                    'message' => 'Minimum 3 cycles required for accurate report',
                    'available_cycles' => $allCycles->count(),
                    'required_cycles' => 3,
                ];
            }
        }

        // Determine cycles for calculation (max 6)
        $cyclesCount = $allCycles->count();
        if ($cyclesCount >= 6) {
            $cycles = $allCycles->take(6)->values();
            $dataSource = '6_latest_cycles';
        } else {
            $cycles = $allCycles->values();
            $dataSource = 'all_available_cycles';
        }

        return [
            'status' => 'success',
            'data_info' => [
                'total_cycles_used' => $cycles->count(),
                'total_cycles_available' => $cyclesCount,
                'data_source' => $dataSource,
                'is_post_resume' => $trackingStatus && $trackingStatus->resumed_at ? true : false,
                'calculation_start_date' => $cycles->last()->start_date ?? null,
            ],
            'statistics' => [
                'cycle_length' => $this->calculateCycleLength($cycles),
                'cycle_variation' => $this->calculateCycleVariation($cycles),
                'period_duration' => $this->calculatePeriodDuration($cycles),
            ],
        ];
    }


    public function generateDashboardReport($userId)
    {
        // Get statistics
        $statisticReport = $this->generateReport($userId);
        
        // Handle insufficient data
        if ($statisticReport['status'] !== 'success') {
            return $statisticReport;
        }

        // Get cycle history
        $cycleHistory = $this->getCycleHistory($userId);

        return [
            'status' => 'success',
            'statistics' => $statisticReport['statistics'],
            'data_info' => array_merge(
                $statisticReport['data_info'],
                [
                    'cycle_history_6_months' => $cycleHistory,
                ]
            ),
        ];
    }


    private function getCycleHistory($userId)
    {
        // Ambil 6 siklus terakhir yang punya cycle_length
        $cycles = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->whereNotNull('cycle_length')  // Hanya cycle yang complete
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get();

        if ($cycles->isEmpty()) {
            return [];
        }

        // Group by YEAR
        return $cycles->groupBy(function($cycle) {
            return Carbon::parse($cycle->start_date)->year;
        })->map(function($yearCycles, $year) {
            $cycleList = $yearCycles->map(function($cycle) {
                $startDate = Carbon::parse($cycle->start_date);
                
                // Hitung next start date berdasarkan cycle length
                $nextStartDate = $startDate->copy()->addDays($cycle->cycle_length);
                
                return [
                    'id' => $cycle->id,
                    'display' => $startDate->format('d M') . ' - ' . 
                                $nextStartDate->format('d M') . 
                                ' (' . $cycle->cycle_length . ' days)',
                    'start_date' => $startDate->format('Y-m-d'),
                    'next_cycle_date' => $nextStartDate->format('Y-m-d'),
                    'cycle_length' => $cycle->cycle_length,
                    'period_duration' => $cycle->period_duration,
                ];
            })->values()->all();

            return [
                'year' => $year,
                'cycles' => $cycleList,
                'total_cycles' => count($cycleList),
            ];
        })->values()->all();
    }

    private function calculateCycleLength($cycles)
    {
        $cycleLengths = $cycles->pluck('cycle_length')
            ->filter(fn($val) => $val && $val > 0 && $val <= 60)
            ->values()
            ->all();

        if (empty($cycleLengths)) {
            return [
                'average' => null,
                'status' => 'no_data',
                'reference' => '24-38 days (FIGO 2018)',
            ];
        }

        $average = round(array_sum($cycleLengths) / count($cycleLengths));
        $status = ($average < self::FIGO_CYCLE_MIN || $average > self::FIGO_CYCLE_MAX) ? 'abnormal' : 'normal';

        return [
            'average' => $average,
            'status' => $status,
            'reference' => '24-38 days (FIGO 2018)',
        ];
    }

    private function calculateCycleVariation($cycles)
    {
        $cycleLengths = $cycles->pluck('cycle_length')
            ->filter(fn($val) => $val && $val > 0 && $val <= 60)
            ->values()
            ->toArray();

        if (count($cycleLengths) < 2) {
            return [
                'value' => null,
                'status' => 'insufficient_data',
                'reference' => '≤9 days (FIGO 2018)',
            ];
        }

        $variation = $this->calculateStandardDeviation($cycleLengths);
        $status = $variation <= self::FIGO_VARIATION_MAX ? 'normal' : 'abnormal';

        return [
            'value' => round($variation),
            'status' => $status,
            'reference' => '≤9 days (FIGO 2018)',
        ];
    }

    
    private function calculatePeriodDuration($cycles)
    {
        $durations = $cycles->pluck('period_duration')
            ->filter(fn($val) => $val && $val > 0 && $val <= 15)
            ->values()
            ->toArray();

        if (empty($durations)) {
            return [
                'average' => null,
                'status' => 'no_data',
                'reference' => '≤8 days (FIGO 2018)',
            ];
        }

        $average = round(array_sum($durations) / count($durations));
        $status = $average <= self::FIGO_DURATION_MAX ? 'normal' : 'abnormal';

        return [
            'average' => $average,
            'status' => $status,
            'reference' => '≤8 days (FIGO 2018)',
        ];
    }


    private function calculateStandardDeviation($values)
    {
        if (count($values) < 2) return 0;

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($val) use ($mean) {
            return pow($val - $mean, 2);
        }, $values);
        
        $variance = array_sum($squaredDiffs) / count($values);
        return sqrt($variance);
    }
}
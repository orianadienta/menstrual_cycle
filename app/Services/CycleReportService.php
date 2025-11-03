<?php
namespace App\Services;

use App\Models\Cycle;

class CycleReportService
{
    // Standar FIGO 2018
    const FIGO_CYCLE_MIN = 24;
    const FIGO_CYCLE_MAX = 38;
    const FIGO_VARIATION_MAX = 9; // ±4 hari = range 9 hari
    const FIGO_DURATION_MAX = 8;

    /**
     * Generate laporan statistik siklus menstruasi
     * Menggunakan 6 siklus terakhir (atau semua jika < 6)
     * 
     * @param int $userId
     * @return array
     */
    public function generateReport($userId)
    {
        // Ambil semua siklus yang valid
        $allCycles = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->get();

        // Validasi: minimal 3 siklus
        if ($allCycles->count() < 3) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Minimal 3 siklus diperlukan untuk laporan yang akurat',
                'available_cycles' => $allCycles->count(),
                'required_cycles' => 3,
            ];
        }

        // Tentukan siklus untuk kalkulasi
        $cyclesCount = $allCycles->count();
        if ($cyclesCount >= 6) {
            $cycles = $allCycles->take(6)->values(); // Add values() to reset keys
            $dataSource = '6_latest_cycles';
        } else {
            $cycles = $allCycles->values(); // Add values() to reset keys
            $dataSource = 'all_available_cycles';
        }

        return [
            'status' => 'success',
            'data_info' => [
                'total_cycles_used' => $cycles->count(),
                'total_cycles_available' => $cyclesCount,
                'data_source' => $dataSource,
            ],
            'statistics' => [
                'cycle_length' => $this->calculateCycleLength($cycles),
                'cycle_variation' => $this->calculateCycleVariation($cycles),
                'period_duration' => $this->calculatePeriodDuration($cycles),
            ],
        ];
    }

    /**
     * Hitung rata-rata panjang siklus
     */
    private function calculateCycleLength($cycles)
    {
        // Convert to array to avoid serialization issues
        $cycleLengths = $cycles->pluck('cycle_length')
            ->filter(fn($val) => $val && $val > 0 && $val <= 60)
            ->values()
            ->all(); // Convert Collection to array

        if (empty($cycleLengths)) {
            return [
                'average' => null,
                'status' => 'no_data',
                'reference' => '24-38 hari (FIGO 2018)',
            ];
        }

        $average = round(array_sum($cycleLengths) / count($cycleLengths));

        // Status berdasarkan FIGO
        $status = 'normal';
        if ($average < self::FIGO_CYCLE_MIN || $average > self::FIGO_CYCLE_MAX) {
            $status = 'abnormal';
        }

        return [
            'average' => $average,
            'status' => $status,
            'reference' => '24-38 hari (FIGO 2018)',
        ];
    }

    /**
     * Hitung variasi siklus
     */
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
                'reference' => '≤9 hari (FIGO 2018)',
            ];
        }

        $variation = $this->calculateStandardDeviation($cycleLengths);

        // Status berdasarkan FIGO (≤9 hari = normal)
        $status = $variation <= self::FIGO_VARIATION_MAX ? 'normal' : 'abnormal';

        return [
            'value' => round($variation),
            'status' => $status,
            'reference' => '≤9 hari (FIGO 2018)',
        ];
    }

    /**
     * Hitung rata-rata durasi menstruasi
     */
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
                'reference' => '≤8 hari (FIGO 2018)',
            ];
        }

        $average = round(array_sum($durations) / count($durations));

        // Status berdasarkan FIGO (≤8 hari = normal)
        $status = $average <= self::FIGO_DURATION_MAX ? 'normal' : 'abnormal';

        return [
            'average' => $average,
            'status' => $status,
            'reference' => '≤8 hari (FIGO 2018)',
        ];
    }

    /**
     * Hitung standar deviasi untuk variasi siklus
     */
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



// class CycleReportService
// {
//     // Standar FIGO 2018
//     const FIGO_CYCLE_MIN = 24;
//     const FIGO_CYCLE_MAX = 38;
//     const FIGO_VARIATION_MAX = 9; // ±4 hari = range 9 hari
//     const FIGO_DURATION_MAX = 8;

//     /**
//      * Generate laporan statistik siklus menstruasi
//      * Menggunakan 6 siklus terakhir (atau semua jika < 6)
//      * 
//      * @param int $userId
//      * @return array
//      */
//     public function generateReport($userId)
//     {
//         // Ambil semua siklus yang valid
//         $allCycles = Cycle::where('user_id', $userId)
//             ->whereNotNull('end_date')
//             ->orderBy('start_date', 'desc')
//             ->get();

//         // Validasi: minimal 3 siklus
//         if ($allCycles->count() < 3) {
//             return [
//                 'status' => 'insufficient_data',
//                 'message' => 'Minimal 3 siklus diperlukan untuk laporan yang akurat',
//                 'available_cycles' => $allCycles->count(),
//                 'required_cycles' => 3,
//             ];
//         }

//         // Tentukan siklus untuk kalkulasi
//         $cyclesCount = $allCycles->count();
//         if ($cyclesCount >= 6) {
//             $cycles = $allCycles->take(6);
//             $dataSource = '6_latest_cycles';
//         } else {
//             $cycles = $allCycles;
//             $dataSource = 'all_available_cycles';
//         }

//         return [
//             'status' => 'success',
//             'data_info' => [
//                 'total_cycles_used' => $cycles->count(),
//                 'total_cycles_available' => $cyclesCount,
//                 'data_source' => $dataSource,
//             ],
//             'statistics' => [
//                 'cycle_length' => $this->calculateCycleLength($cycles),
//                 'cycle_variation' => $this->calculateCycleVariation($cycles),
//                 'period_duration' => $this->calculatePeriodDuration($cycles),
//             ],
//         ];
//     }

//     /**
//      * Hitung rata-rata panjang siklus
//      */
//     private function calculateCycleLength($cycles)
//     {
//         $cycleLengths = $cycles->pluck('cycle_length')
//             ->filter(fn($val) => $val && $val > 0 && $val <= 60)
//             ->values()
//             ->toArray();

//         if (empty($cycleLengths)) {
//             return [
//                 'average' => null,
//                 'status' => 'no_data',
//                 'reference' => '24-38 hari (FIGO 2018)',
//             ];
//         }

//         $average = round(array_sum($cycleLengths) / count($cycleLengths));

//         // Status berdasarkan FIGO
//         $status = 'normal';
//         if ($average < self::FIGO_CYCLE_MIN || $average > self::FIGO_CYCLE_MAX) {
//             $status = 'abnormal';
//         }

//         return [
//             'average' => $average,
//             'status' => $status,
//             'reference' => '24-38 hari (FIGO 2018)',
//         ];
//     }

//     /**
//      * Hitung variasi siklus
//      */
//     private function calculateCycleVariation($cycles)
//     {
//         $cycleLengths = $cycles->pluck('cycle_length')
//             ->filter(fn($val) => $val && $val > 0 && $val <= 60)
//             ->values()
//             ->toArray();

//         if (count($cycleLengths) < 2) {
//             return [
//                 'value' => null,
//                 'status' => 'insufficient_data',
//                 'reference' => '≤9 hari (FIGO 2018)',
//             ];
//         }

//         $variation = $this->calculateStandardDeviation($cycleLengths);

//         // Status berdasarkan FIGO (≤9 hari = normal)
//         $status = $variation <= self::FIGO_VARIATION_MAX ? 'normal' : 'abnormal';

//         return [
//             'value' => round($variation),
//             'status' => $status,
//             'reference' => '≤9 hari (FIGO 2018)',
//         ];
//     }

//     /**
//      * Hitung rata-rata durasi menstruasi
//      */
//     private function calculatePeriodDuration($cycles)
//     {
//         $durations = $cycles->pluck('period_duration')
//             ->filter(fn($val) => $val && $val > 0 && $val <= 15)
//             ->values()
//             ->toArray();

//         if (empty($durations)) {
//             return [
//                 'average' => null,
//                 'status' => 'no_data',
//                 'reference' => '≤8 hari (FIGO 2018)',
//             ];
//         }

//         $average = round(array_sum($durations) / count($durations));

//         // Status berdasarkan FIGO (≤8 hari = normal)
//         $status = $average <= self::FIGO_DURATION_MAX ? 'normal' : 'abnormal';

//         return [
//             'average' => $average,
//             'status' => $status,
//             'reference' => '≤8 hari (FIGO 2018)',
//         ];
//     }

//     /**
//      * Hitung standar deviasi untuk variasi siklus
//      */
//     private function calculateStandardDeviation($values)
//     {
//         if (count($values) < 2) return 0;

//         $mean = array_sum($values) / count($values);
//         $squaredDiffs = array_map(function($val) use ($mean) {
//             return pow($val - $mean, 2);
//         }, $values);
        
//         $variance = array_sum($squaredDiffs) / count($values);
//         return sqrt($variance);
//     }
// }
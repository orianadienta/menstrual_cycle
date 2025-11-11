<?php

namespace App\Constants;

class RecommendationContent
{
    const ONBOARDING = [
        'title' => 'Mulai Tracking',
        'content' => 'Catat minimal 3 siklus agar rekomendasi lebih akurat.',
        'category' => 'edukasi',
        'priority' => 'low',
    ];

    const HEALTHY_CYCLE = [
        'title' => 'Siklus Sehat! 🎉',
        'content' => 'Siklus kamu berada dalam rentang normal (24–38 hari). Pertahankan pola hidup sehat, ya!',
        'category' => 'preventif',
        'priority' => 'low',
    ];

    const HEALTHY_LIFESTYLE = [
        'title' => 'Jaga Kesehatan',
        'content' => 'Konsumsi makanan bergizi, olahraga rutin, dan tidur cukup 7–8 jam per hari.',
        'category' => 'preventif',
        'priority' => 'low',
    ];

    const TRACK_SYMPTOMS_PROMPT = [
        'title' => 'Catat Gejala',
        'content' => 'Tracking gejala membantu deteksi perubahan hormon lebih awal.',
        'category' => 'edukasi',
        'priority' => 'low',
    ];

    const SYMPTOM_GENERAL = [
        'title' => 'Kelola Gejala',
        'content' => 'Siklus normal, tapi ada gejala ringan. Catat polanya untuk evaluasi lebih baik.',
        'category' => 'kesehatan',
        'priority' => 'medium',
    ];

    const SYMPTOM_PAIN = [
        'title' => 'Manajemen Nyeri',
        'content' => 'Gunakan kompres hangat, stretching ringan, dan istirahat cukup untuk bantu meredakan nyeri.',
        'category' => 'kesehatan',
        'priority' => 'medium',
    ];

    const SYMPTOM_MOOD = [
        'title' => 'Kesehatan Mental',
        'content' => 'Perubahan mood adalah hal wajar. Coba journaling atau meditasi untuk menstabilkan emosi.',
        'category' => 'kesehatan',
        'priority' => 'low',
    ];

    const SYMPTOM_SLEEP = [
        'title' => 'Tidur Berkualitas',
        'content' => 'Tidur cukup dan hindari kafein 6 jam sebelum tidur untuk bantu ritme hormon tetap stabil.',
        'category' => 'kesehatan',
        'priority' => 'low',
    ];

    const IRREGULAR_ALERT = [
        'title' => '⚠️ Siklus Tidak Teratur',
        'content' => 'Siklus di luar rentang 24–38 hari. Coba pantau pola tidur dan stres.',
        'category' => 'kesehatan',
        'priority' => 'high',
    ];

    const IRREGULAR_LIFESTYLE = [
        'title' => 'Evaluasi Gaya Hidup',
        'content' => 'Perhatikan pola makan, stres, dan durasi tidur untuk bantu stabilkan siklus.',
        'category' => 'kesehatan',
        'priority' => 'high',
    ];

    const IRREGULAR_URGENT = [
        'title' => '🚨 Perlu Perhatian',
        'content' => 'Siklus tidak teratur dengan gejala berat. Pertimbangkan konsultasi dokter kandungan.',
        'category' => 'kesehatan',
        'priority' => 'urgent',
    ];

    const IRREGULAR_MEDICAL_ADVICE = [
        'title' => 'Saran Medis',
        'content' => 'Jika siklus tidak teratur lebih dari 3 bulan, lakukan pemeriksaan ke dokter.',
        'category' => 'kesehatan',
        'priority' => 'medium',
    ];

    const CONSULTATION_PREP = [
        'title' => 'Persiapan Konsultasi',
        'content' => 'Catat durasi siklus, gejala, dan riwayat keluarga untuk membantu dokter menganalisis.',
        'category' => 'edukasi',
        'priority' => 'medium',
    ];

    const CONDITION_MONITORING = [
        'title' => 'Monitor Kesehatan',
        'content' => 'Lakukan pemeriksaan rutin sesuai anjuran dokter dan pantau gejala setiap bulan.',
        'category' => 'kesehatan',
        'priority' => 'medium',
    ];

    const CONDITIONS = [
        'Polycystic Ovary Syndrome (PCOS)' => [
            'title' => 'Manajemen PCOS',
            'content' => 'Fokus pada pola makan rendah indeks glikemik, olahraga 150 menit/minggu, dan jaga berat badan ideal untuk seimbangkan hormon.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Endometriosis' => [
            'title' => 'Manajemen Endometriosis',
            'content' => 'Kurangi makanan pemicu inflamasi, kelola stres, dan konsultasi dokter bila nyeri berat.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Hypothyroidism' => [
            'title' => 'Monitor Hipotiroid',
            'content' => 'Minum obat sesuai resep dokter dan periksa TSH setiap 6–12 bulan untuk menjaga keseimbangan hormon.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Hyperthyroidism' => [
            'title' => 'Perhatikan HiperTiroid',
            'content' => 'Ikuti terapi dokter dan hindari konsumsi yodium berlebih. Pantau perubahan siklus.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Uterine Fibroids (Leiomyoma)' => [
            'title' => 'Pantau Mioma Uteri',
            'content' => 'Catat volume perdarahan dan durasi haid. Jika darah keluar banyak atau sering pusing, konsultasi dokter.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Adenomyosis' => [
            'title' => 'Atasi Nyeri Adenomiosis',
            'content' => 'Gunakan kompres hangat, olahraga ringan, dan istirahat cukup. Jika nyeri berat, segera konsultasi.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],

        'Perimenopause (Premenopause)' => [
            'title' => 'Masa Perimenopause',
            'content' => 'Perubahan siklus bisa terjadi. Jaga gaya hidup sehat, tidur cukup, dan catat gejala untuk pantauan.',
            'category' => 'edukasi',
            'priority' => 'low',
        ],

        'Premenstrual Dysphoric Disorder (PMDD)' => [
            'title' => 'Kelola PMDD',
            'content' => 'Fokus pada kesehatan mental, olahraga rutin, hindari kafein, dan pertimbangkan konseling jika mood swing berat.',
            'category' => 'kesehatan',
            'priority' => 'high',
        ],

        'Anemia' => [
            'title' => 'Cegah & Atasi Anemia',
            'content' => 'Konsumsi makanan kaya zat besi dan vitamin C. Jika lemas terus, konsultasikan pemeriksaan darah.',
            'category' => 'kesehatan',
            'priority' => 'medium',
        ],
    ];

    const SYMPTOM_CATEGORIES = [
        'Nyeri' => self::SYMPTOM_PAIN,
        'Perasaan' => self::SYMPTOM_MOOD,
        'Kualitas Tidur' => self::SYMPTOM_SLEEP,
    ];
}

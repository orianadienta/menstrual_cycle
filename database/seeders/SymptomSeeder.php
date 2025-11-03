<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Symptom;
use App\Models\SymptomCategory;

class SymptomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            'Perasaan' => ['Bahagia', 'Sedih', 'Sensitif', 'Marah', 'Semangat', 'Cemas', 'Perubahan suasana hati'],
            'Nyeri' => ['Tidak nyeri', 'Kram', 'Ovulasi', 'Nyeri payudara', 'Sakit kepala', 'Migrain', 'Punggung bawah'],
            'Kualitas Tidur' => ['Sulit tidur', 'Bangun merasa segar', 'Bangun merasa lelah', 'Tidur nyenyak'],
        ];

        foreach ($categories as $categoryName => $symptoms) {
            $category = SymptomCategory::create(['category_name' => $categoryName]);
            foreach ($symptoms as $symptomName) {
                Symptom::create([
                    'category_id' => $category->id,
                    'symptom_name' => $symptomName,
                ]);
            }
        }
    }
}

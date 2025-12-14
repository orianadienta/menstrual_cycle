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
            'Mood' => [
                'Happy',
                'Sad',
                'Sensitive',
                'Angry',
                'Energetic',
                'Anxious',
                'Mood swings',
            ],

            'Pain' => [
                'No pain',
                'Cramps',
                'Ovulation pain',
                'Breast tenderness',
                'Headache',
                'Migraine',
                'Lower back pain',
            ],

            'Sleep Quality' => [
                'Difficulty sleeping',
                'Wake up refreshed',
                'Wake up tired',
                'Deep sleep',
            ],
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

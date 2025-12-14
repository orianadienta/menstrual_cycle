<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\HealthCondition;
use Illuminate\Database\Seeder;

class HealthConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            [
                'condition_name' => 'Polycystic Ovary Syndrome (PCOS)',
                'description' => 'A common hormonal disorder in women of reproductive age, characterized by enlarged ovaries and the formation of small cysts. Symptoms may include irregular periods, excess hair growth, and acne.',
            ],
            [
                'condition_name' => 'Endometriosis',
                'description' => 'A condition where tissue similar to the endometrium grows outside the uterus, causing severe menstrual pain, cramps, and sometimes abnormal bleeding.',
            ],
            [
                'condition_name' => 'Hypothyroidism',
                'description' => 'An underactive thyroid gland slows metabolism and disrupts reproductive hormonal balance, leading to infrequent, heavy, or irregular menstruation.',
            ],
            [
                'condition_name' => 'Hyperthyroidism',
                'description' => 'Excess thyroid hormones increase metabolism and may lower estrogen levels, leading to shorter, lighter, or infrequent periods.',
            ],
            [
                'condition_name' => 'Uterine Fibroids (Leiomyoma)',
                'description' => 'Benign tumors in the uterine muscle that can cause heavy or prolonged menstruation, pelvic pain, and a feeling of fullness in the abdomen.',
            ],
            [
                'condition_name' => 'Adenomyosis',
                'description' => 'A condition where endometrial tissue grows into the uterine muscle, causing very painful periods and heavy or prolonged bleeding.',
            ],
            [
                'condition_name' => 'Perimenopause (Premenopause)',
                'description' => 'The transitional period leading to menopause when ovulation becomes irregular, causing menstrual cycles to become longer, shorter, or inconsistent in flow.',
            ],
            [
                'condition_name' => 'Premenstrual Dysphoric Disorder (PMDD)',
                'description' => 'A severe form of premenstrual syndrome (PMS) characterized by extreme mood changes, irritability, and emotional symptoms before menstruation.',
            ],
            [
                'condition_name' => 'Anemia',
                'description' => 'A condition marked by low hemoglobin levels due to excessive menstrual blood loss, leading to fatigue, dizziness, and pale skin.',
            ],
        ];

        foreach ($conditions as $c) {
            HealthCondition::create($c);
        }
    }
}

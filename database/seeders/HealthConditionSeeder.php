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
                'description' => 'Gangguan hormonal yang umum pada wanita usia reproduksi, ditandai dengan ovarium yang membesar dan pembentukan kista kecil. Gejala termasuk menstruasi tidak teratur, pertumbuhan rambut berlebih, dan jerawat.'
            ],
            [
                'condition_name' => 'Endometriosis',
                'description' => 'Kondisi di mana jaringan mirip endometrium tumbuh di luar rahim, menyebabkan nyeri haid berat, kram, dan terkadang perdarahan tidak normal.'
            ],
            [
                'condition_name' => 'Hypothyroidism',
                'description' => 'Kelenjar tiroid yang kurang aktif dapat memperlambat metabolisme dan mengganggu keseimbangan hormon reproduksi, menyebabkan menstruasi jarang, berat, atau tidak teratur.'
            ],
            [
                'condition_name' => 'Hyperthyroidism',
                'description' => 'Kelebihan hormon tiroid mempercepat metabolisme dan dapat menurunkan kadar estrogen, menyebabkan haid menjadi lebih pendek, ringan, atau jarang.'
            ],
            [
                'condition_name' => 'Uterine Fibroids (Leiomyoma)',
                'description' => 'Tumor jinak pada otot rahim yang dapat menyebabkan menstruasi berat, lama, nyeri panggul, dan perut terasa penuh.'
            ],
            [
                'condition_name' => 'Adenomyosis',
                'description' => 'Jaringan endometrium tumbuh ke dalam otot rahim, menyebabkan haid sangat nyeri dan perdarahan berat atau lama.'
            ],
            [
                'condition_name' => 'Perimenopause (Premenopause)',
                'description' => 'Masa transisi menuju menopause di mana ovulasi menjadi tidak teratur, menyebabkan siklus haid berubah panjang atau pendek, dan volume darah tidak menentu.'
            ],
            [
                'condition_name' => 'Premenstrual Dysphoric Disorder (PMDD)',
                'description' => 'Bentuk berat dari sindrom pramenstruasi (PMS) yang ditandai dengan perubahan mood ekstrem, iritabilitas, dan gejala emosional menjelang haid.'
            ],
            [
                'condition_name' => 'Anemia',
                'description' => 'Kadar hemoglobin rendah akibat kehilangan darah menstruasi berlebih, menyebabkan kelelahan, pusing, dan kulit pucat.'
            ],
        ];

        foreach ($conditions as $c) {
            HealthCondition::create($c);
        }
    }
}

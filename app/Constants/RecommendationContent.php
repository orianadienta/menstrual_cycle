<?php

namespace App\Constants;

class RecommendationContent
{
    const ONBOARDING = [
        'title' => 'Start Tracking',
        'content' => 'Track at least 3 cycles for more accurate recommendations.',
        'category' => 'education',
        'priority' => 'low',
    ];

    const HEALTHY_CYCLE = [
        'title' => 'Healthy Cycle! 🎉',
        'content' => 'Your cycle is within the normal range (24–38 days). Keep maintaining a healthy lifestyle!',
        'category' => 'preventive',
        'priority' => 'low',
    ];

    const HEALTHY_LIFESTYLE = [
        'title' => 'Maintain Your Health',
        'content' => 'Eat nutritious food, exercise regularly, and get 7–8 hours of sleep daily.',
        'category' => 'preventive',
        'priority' => 'low',
    ];

    const TRACK_SYMPTOMS_PROMPT = [
        'title' => 'Track Symptoms',
        'content' => 'Symptom tracking helps detect hormonal changes earlier.',
        'category' => 'education',
        'priority' => 'low',
    ];

    const SYMPTOM_GENERAL = [
        'title' => 'Manage Symptoms',
        'content' => 'Your cycle is normal, but there are mild symptoms. Track the patterns for better evaluation.',
        'category' => 'health',
        'priority' => 'medium',
    ];

    const SYMPTOM_PAIN = [
        'title' => 'Pain Management',
        'content' => 'Use warm compresses, light stretching, and adequate rest to relieve discomfort.',
        'category' => 'health',
        'priority' => 'medium',
    ];

    const SYMPTOM_MOOD = [
        'title' => 'Mental Well-being',
        'content' => 'Mood changes are normal. Try journaling or meditation to stabilize emotions.',
        'category' => 'health',
        'priority' => 'low',
    ];

    const SYMPTOM_SLEEP = [
        'title' => 'Quality Sleep',
        'content' => 'Get enough rest and avoid caffeine 6 hours before bedtime to support hormonal balance.',
        'category' => 'health',
        'priority' => 'low',
    ];

    const IRREGULAR_ALERT = [
        'title' => '⚠️ Irregular Cycle',
        'content' => 'Your cycle is outside the 24–38 day range. Try monitoring sleep and stress patterns.',
        'category' => 'health',
        'priority' => 'high',
    ];

    const IRREGULAR_LIFESTYLE = [
        'title' => 'Lifestyle Evaluation',
        'content' => 'Pay attention to diet, stress levels, and sleep duration to help stabilize your cycle.',
        'category' => 'health',
        'priority' => 'high',
    ];

    const IRREGULAR_URGENT = [
        'title' => '🚨 Needs Attention',
        'content' => 'Irregular cycle with severe symptoms. Consider consulting an OB-GYN.',
        'category' => 'health',
        'priority' => 'urgent',
    ];

    const IRREGULAR_MEDICAL_ADVICE = [
        'title' => 'Medical Advice',
        'content' => 'If your cycle is irregular for more than 3 months, consider getting a medical check-up.',
        'category' => 'health',
        'priority' => 'medium',
    ];

    const CONSULTATION_PREP = [
        'title' => 'Consultation Preparation',
        'content' => 'Record cycle duration, symptoms, and family history to help your doctor analyze your condition.',
        'category' => 'education',
        'priority' => 'medium',
    ];

    const CONDITION_MONITORING = [
        'title' => 'Health Monitoring',
        'content' => 'Follow routine check-ups as advised and monitor symptoms every month.',
        'category' => 'health',
        'priority' => 'medium',
    ];

    const CONDITIONS = [
        'Polycystic Ovary Syndrome (PCOS)' => [
            'title' => 'PCOS Management',
            'content' => 'Focus on low-GI foods, exercise 150 minutes/week, and maintain a healthy weight to balance hormones.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Endometriosis' => [
            'title' => 'Endometriosis Management',
            'content' => 'Reduce inflammatory foods, manage stress, and consult a doctor if the pain is severe.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Hypothyroidism' => [
            'title' => 'Monitor Hypothyroidism',
            'content' => 'Take medication as prescribed and check TSH levels every 6–12 months to maintain hormonal balance.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Hyperthyroidism' => [
            'title' => 'Monitor Hyperthyroidism',
            'content' => 'Follow your doctor’s therapy plan and avoid excessive iodine intake. Monitor cycle changes.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Uterine Fibroids (Leiomyoma)' => [
            'title' => 'Monitor Uterine Fibroids',
            'content' => 'Track bleeding volume and menstrual duration. If bleeding is heavy or causes dizziness, consult a doctor.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Adenomyosis' => [
            'title' => 'Manage Adenomyosis Pain',
            'content' => 'Use warm compresses, light exercise, and proper rest. Consult a doctor if the pain is severe.',
            'category' => 'health',
            'priority' => 'medium',
        ],

        'Perimenopause (Premenopause)' => [
            'title' => 'Perimenopause Phase',
            'content' => 'Cycle changes may occur. Maintain a healthy lifestyle, get enough sleep, and track symptoms regularly.',
            'category' => 'education',
            'priority' => 'low',
        ],

        'Premenstrual Dysphoric Disorder (PMDD)' => [
            'title' => 'Manage PMDD',
            'content' => 'Focus on mental health, exercise regularly, avoid caffeine, and consider counseling if mood swings are severe.',
            'category' => 'health',
            'priority' => 'high',
        ],

        'Anemia' => [
            'title' => 'Prevent & Manage Anemia',
            'content' => 'Consume iron-rich foods and vitamin C. If fatigue persists, consider a blood test.',
            'category' => 'health',
            'priority' => 'medium',
        ],
    ];

    const SYMPTOM_CATEGORIES = [
        'Pain' => self::SYMPTOM_PAIN,
        'Mood' => self::SYMPTOM_MOOD,
        'Sleep Quality' => self::SYMPTOM_SLEEP,
    ];
}

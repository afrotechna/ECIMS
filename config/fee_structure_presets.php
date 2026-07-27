<?php

return [

    'allow_custom_amounts' => (bool) env('FEE_STRUCTURE_ALLOW_CUSTOM_AMOUNTS', false),

    'packages' => [
        'standard' => [
            'label' => 'Standard package',
            'sem1_tuition' => 595_000,
            'sem1_nhif' => 50_400,
            'sem1_nactvet_qa' => 20_000,
            'sem2_tuition_continuous' => 325_000,
            'sem2_tuition_repeat_transfer' => 595_000,
            'accommodation' => 0,
            'other_charges' => 0,
        ],
    ],

    'pick' => [
        'sem1_tuition' => [
            595_000 => '595,000 · Sem I tuition',
        ],
        'sem1_nhif' => [
            50_400 => '50,400 · NHIF',
        ],
        'sem1_nactvet_qa' => [
            20_000 => '20,000 · QA',
        ],
        'sem2_tuition_continuous' => [
            325_000 => '325,000 · Sem II (continuing)',
        ],
        'sem2_tuition_repeat_transfer' => [
            595_000 => '595,000 · Sem II (repeat / transfer)',
        ],
        'accommodation' => [
            0 => '0',
        ],
        'other_charges' => [
            0 => '0',
        ],
    ],
];

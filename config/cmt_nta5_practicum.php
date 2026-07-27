<?php

/**
 * CMT NTA Level 5 clinical practicum catalogue (summary + sync fallback).
 * Full procedure list: import from PRACTICUM GUIDE NTA L5_Tutors.pdf
 *   php artisan clinical:import-cmt-practicum --level=5 --reparse
 */
return [
    'source' => 'Practicum Guide NTA Level 5 Clinical Medicine (Tutors)',
    'programme_code' => 'CMT',
    'nta_level' => 5,
    'assessment_methods' => require __DIR__.'/cmt_practicum_assessment_methods.php',
    'rotation_areas' => [
        'internal_medicine' => 'Internal Medicine',
        'surgery' => 'Surgery',
        'obstetrics_gynaecology' => 'Obstetrics and Gynaecology',
        'paediatrics' => 'Paediatric and Child Health',
        'community_health' => 'Community Health',
        'clinical_laboratory' => 'Clinical Laboratory',
        'patient_care' => 'Patient Care',
    ],
    'procedures' => [],
];

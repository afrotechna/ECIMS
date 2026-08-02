<?php

return [
    'institution_name' => env('COLLEGE_INSTITUTION_NAME', 'MUSOMA COLLEGE OF HEALTH AND ALLIED SCIENCES'),
    'school_name' => env('COLLEGE_SCHOOL_NAME', 'MUSOMA COLLEGE OF HEALTH AND ALLIED SCIENCES'),
    'results_board' => env('COLLEGE_RESULTS_BOARD', 'TANGANYIKA MEDICAL TRAINING BOARD'),

    /** CA (AVCA) is marked out of this maximum (e.g. 40 for “40%” component). */
    'ca_max_mark' => (float) env('COLLEGE_CA_MAX_MARK', 40),

    /** Minimum % of ca_max_mark required to PASS CA and sit the end-of-semester exam for that module. */
    'ca_pass_percent' => (float) env('COLLEGE_CA_PASS_PERCENT', 40),

    /** CA theory-component average must be strictly greater than this to count toward CA (used to explain a CA(40%) = 0.0 fail). */
    'ca_theory_pass_mark' => (float) env('COLLEGE_CA_THEORY_PASS_MARK', 10.1),

    /** CA practical-component average must be strictly greater than this to count toward CA (used to explain a CA(40%) = 0.0 fail). */
    'ca_practical_pass_mark' => (float) env('COLLEGE_CA_PRACTICAL_PASS_MARK', 50),

    /** End-of-semester exam (AVES) is marked out of this maximum (e.g. 60 for the "60%" component). */
    'exam_max_mark' => (float) env('COLLEGE_EXAM_MAX_MARK', 60),

    /**
     * Month (1–12) when a new academic year starts (Tanzania colleges often use July).
     * Before this month in the calendar year, the session is still the year that began last July.
     */
    'academic_year_start_month' => (int) env('COLLEGE_ACADEMIC_YEAR_START_MONTH', 7),

    /**
     * SMS when semester results are released (Twilio).
     * Set TWILIO_ENABLED=true and credentials in .env before enabling auto-notify.
     */
    'result_sms' => [
        'notify_on_final_import' => (bool) env('RESULT_SMS_ON_FINAL_IMPORT', false),
        'notify_on_ca_import' => (bool) env('RESULT_SMS_ON_CA_IMPORT', false),
        'notify_guardian' => (bool) env('RESULT_SMS_NOTIFY_GUARDIAN', true),
        'notify_student' => (bool) env('RESULT_SMS_NOTIFY_STUDENT', true),
        'dedupe' => (bool) env('RESULT_SMS_DEDUPE', true),
        'portal_path' => env('RESULT_SMS_PORTAL_PATH', '/my-module-results'),
        /** Result SMS wording: en or sw (Swahili). */
        'locale' => strtolower((string) env('RESULT_SMS_LOCALE', 'en')),
    ],

    'clinical_sms' => [
        'enabled' => (bool) env('CLINICAL_SMS_ENABLED', true),
    ],

    /** External systems (links only — no API integration unless noted). */
    'integrations' => [
        'moodle_url' => env('MOODLE_URL', ''),
        'library_url' => env('LIBRARY_SYSTEM_URL', ''),
        'gepg_note' => 'GEPG / mobile money online payment will be connected when bank authorization is complete.',
    ],
];

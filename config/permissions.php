<?php

/**
 * Role → module → allowed actions.
 * Actions: view, create, update, delete
 *
 * Portfolio rules (COHAS):
 * - VP (AFP — Administrative, Financial & Planning): finance, procurement (inventory), accommodation oversight
 * - VP (ARC — Academic, Research & Consultancy): registration, results, exams, clinical, timetable, question bank (programmes, semesters & module catalogue: view only; ICT admin maintains catalogue)
 * - Academic team: VP (ARC), Admission, HOD CMT, HOD MLT, Examination Officer (+ tutors / clinical instructors)
 * - Finance team: VP (AFP), Accountant, Procurement Officer, Secretary (reports), Accommodation Matron
 * - Principal: oversight across portfolios
 * - ICT administrator: system + full access
 */
return [
    'actions' => ['view', 'create', 'update', 'delete'],

    'modules' => [
        'programmes' => 'Programmes & NTA documents',
        'semesters' => 'Semesters',
        'courses' => 'Module catalogue',
        'registrations' => 'Semester registration & wizard',
        'results' => 'Results & transcripts',
        'exams' => 'Exam schedule',
        'timetable' => 'Class timetable',
        'question_bank' => 'Question bank',
        'clinical' => 'Clinical rotation',
        'finance_fees' => 'Fee structures',
        'finance_payments' => 'Payments & ledger charges',
        'finance_reports' => 'Financial & admission reports',
        'students' => 'Student records',
        'accommodation' => 'Hostels, rooms & allocations',
        'college_comms' => 'Announcements & message log',
        'student_affairs' => 'Leave, conduct & graduation',
        'certificate_collection' => 'Certificate collection',
        'inventory' => 'Inventory & assets',
        'institution_docs' => 'Institution documents',
        'calendar' => 'College calendar & events',
        'system' => 'Users, activity log & data export',
    ],

    /**
     * Map route name prefix (first segment) → module. Exceptions listed in route_overrides.
     */
    'route_prefixes' => [
        'programmes' => 'programmes',
        'semesters' => 'semesters',
        'courses' => 'courses',
        'semester-registrations' => 'registrations',
        'registration-wizard' => 'registrations',
        'results' => 'results',
        'exam-slots' => 'exams',
        'timetable-slots' => 'timetable',
        'question-bank' => 'question_bank',
        'clinical-rotations' => 'clinical',
        'clinical-procedures' => 'clinical',
        'clinical-logbook' => 'clinical',
        'fee-structures' => 'finance_fees',
        'payments' => 'finance_payments',
        'students' => 'students',
        'student-documents' => 'students',
        'search' => 'students',
        'hostels' => 'accommodation',
        'rooms' => 'accommodation',
        'accommodation-allocations' => 'accommodation',
        'announcements' => 'college_comms',
        'message-logs' => 'college_comms',
        'leave-applications' => 'student_affairs',
        'conduct-records' => 'student_affairs',
        'graduation-clearances' => 'student_affairs',
        'certificate-collections' => 'certificate_collection',
        'inventory-items' => 'inventory',
        'institution-documents' => 'institution_docs',
        'calendar' => 'calendar',
        'users' => 'system',
        'activity-log' => 'system',
        'export' => 'system',
    ],

    'route_overrides' => [
        'reports.index' => ['finance_reports', 'view'],
        'reports.enrollment' => ['finance_reports', 'view'],
        'reports.enrollment.export' => ['finance_reports', 'view'],
        'reports.arrears' => ['finance_reports', 'view'],
        'reports.arrears.export' => ['finance_reports', 'view'],
        'reports.income' => ['finance_reports', 'view'],
        'reports.income.export' => ['finance_reports', 'view'],
        'reports.payment-by-programme' => ['finance_reports', 'view'],
        'reports.class-list' => ['finance_reports', 'view'],
        'reports.class-list.export' => ['finance_reports', 'view'],
        'reports.fee-collection-summary' => ['finance_reports', 'view'],
        'reports.admission-control-sheet' => ['finance_reports', 'view'],
        'reports.admission-control-sheet.export' => ['finance_reports', 'view'],
        'reports.admission-control-sheet.student' => ['finance_reports', 'view'],
        'reports.students-on-leave' => ['student_affairs', 'view'],
        'reports.academic-standing' => ['results', 'view'],
        'reports.graduation-clearance' => ['student_affairs', 'view'],
        'reports.nactvet-export' => ['results', 'view'],
        'students.ledger' => ['finance_payments', 'view'],
        'students.ledger.charge' => ['finance_payments', 'create'],
        'academics.index' => ['programmes', 'view'],
        // Approving results is a distinct hard-coded role check (ResultApprovalController::ensureApproverRole,
        // Principal/VP ARC only) — mapped to 'view' here since Principal deliberately has no 'create' grant
        // on results (read-only oversight); the real access restriction is the controller-level role check,
        // not this module-permission mapping.
        'results.approvals.index' => ['results', 'view'],
        'results.approvals.approve' => ['results', 'view'],
        'results.approvals.reject' => ['results', 'view'],
    ],

    'matrix' => [
        'administrator' => [
            '*' => ['view', 'create', 'update', 'delete'],
        ],

        'principal' => [
            'programmes' => ['view', 'update'],
            'semesters' => ['view', 'update'],
            'courses' => ['view'],
            'registrations' => ['view', 'create', 'update'],
            'results' => ['view'],
            'exams' => ['view'],
            'timetable' => ['view'],
            'question_bank' => ['view'],
            'clinical' => ['view'],
            'finance_fees' => ['view'],
            'finance_payments' => ['view'],
            'finance_reports' => ['view'],
            'students' => ['view', 'create', 'update'],
            'accommodation' => ['view', 'create', 'update'],
            'college_comms' => ['view', 'create', 'update', 'delete'],
            'student_affairs' => ['view', 'create', 'update'],
            'certificate_collection' => ['view'],
            'inventory' => ['view', 'update'],
            'institution_docs' => ['view', 'create', 'update', 'delete'],
            'calendar' => ['view', 'create', 'update', 'delete'],
            'system' => ['view'],
        ],

        'vice_principal_afp' => [
            'finance_fees' => ['view', 'create', 'update', 'delete'],
            'finance_payments' => ['view', 'create', 'update', 'delete'],
            'finance_reports' => ['view'],
            'inventory' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'update'],
            'accommodation' => ['view', 'create', 'update', 'delete'],
            'registrations' => ['view'],
            'programmes' => ['view'],
            'college_comms' => ['view', 'create', 'update'],
            'student_affairs' => ['view', 'create', 'update'],
            'institution_docs' => ['view', 'create', 'update'],
            'calendar' => ['view', 'create', 'update', 'delete'],
            'system' => ['view'],
        ],

        'procurement_officer' => [
            'inventory' => ['view', 'create', 'update', 'delete'],
            'finance_reports' => ['view'],
            'institution_docs' => ['view'],
            'calendar' => ['view', 'create', 'update'],
        ],

        'secretary' => [
            'finance_reports' => ['view'],
            'students' => ['view'],
            'registrations' => ['view'],
            'programmes' => ['view'],
            'semesters' => ['view'],
            'college_comms' => ['view', 'create', 'update'],
            'institution_docs' => ['view', 'create', 'update'],
            'calendar' => ['view', 'create', 'update', 'delete'],
        ],

        'accommodation_matron' => [
            'accommodation' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'update'],
            'finance_reports' => ['view'],
            'registrations' => ['view'],
            'college_comms' => ['view'],
            'calendar' => ['view', 'create', 'update'],
        ],

        'vice_principal_arc' => [
            'programmes' => ['view'],
            'semesters' => ['view'],
            'courses' => ['view'],
            'registrations' => ['view', 'create', 'update', 'delete'],
            'results' => ['view', 'create', 'update', 'delete'],
            'exams' => ['view', 'create', 'update', 'delete'],
            'timetable' => ['view', 'create', 'update', 'delete'],
            'question_bank' => ['view', 'create', 'update', 'delete'],
            'clinical' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'create', 'update'],
            'student_affairs' => ['view', 'create', 'update'],
            'college_comms' => ['view', 'create', 'update'],
            'institution_docs' => ['view', 'create', 'update'],
            'calendar' => ['view', 'create', 'update', 'delete'],
            'finance_reports' => ['view'],
            'system' => ['view'],
        ],

        'hod_cmt' => [
            'programmes' => ['view'],
            'semesters' => ['view'],
            'courses' => ['view'],
            'registrations' => ['view', 'update'],
            'results' => ['view', 'create', 'update'],
            'exams' => ['view', 'create', 'update'],
            'timetable' => ['view', 'create', 'update'],
            'question_bank' => ['view', 'create', 'update'],
            'clinical' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'update'],
            'student_affairs' => ['view', 'create', 'update'],
            'college_comms' => ['view', 'create', 'update'],
            'accommodation' => ['view'],
            'institution_docs' => ['view'],
            'calendar' => ['view', 'create'],
            'finance_reports' => ['view'],
        ],

        'hod_mlt' => [
            'programmes' => ['view'],
            'semesters' => ['view'],
            'courses' => ['view'],
            'registrations' => ['view', 'update'],
            'results' => ['view', 'create', 'update'],
            'exams' => ['view', 'create', 'update'],
            'timetable' => ['view', 'create', 'update'],
            'question_bank' => ['view', 'create', 'update'],
            'clinical' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'update'],
            'student_affairs' => ['view', 'create', 'update'],
            'college_comms' => ['view', 'create', 'update'],
            'accommodation' => ['view'],
            'institution_docs' => ['view'],
            'calendar' => ['view', 'create'],
            'finance_reports' => ['view'],
        ],

        'admission_officer' => [
            'students' => ['view', 'create', 'update', 'delete'],
            'registrations' => ['view', 'create', 'update'],
            'programmes' => ['view'],
            'semesters' => ['view'],
            'courses' => ['view'],
            'accommodation' => ['view', 'create', 'update', 'delete'],
            'finance_reports' => ['view'],
            'college_comms' => ['view', 'create', 'update'],
            'institution_docs' => ['view', 'create', 'update'],
            'calendar' => ['view', 'create', 'update', 'delete'],
            'certificate_collection' => ['view', 'create', 'update', 'delete'],
        ],

        'examination_officer' => [
            'results' => ['view', 'create', 'update', 'delete'],
            'exams' => ['view', 'create', 'update', 'delete'],
            'question_bank' => ['view', 'create', 'update', 'delete'],
            'students' => ['view'],
            'programmes' => ['view'],
            'courses' => ['view'],
            'semesters' => ['view'],
            'registrations' => ['view'],
            'calendar' => ['view', 'create'],
            'finance_reports' => ['view'],
        ],

        'accountant' => [
            'finance_fees' => ['view', 'create', 'update', 'delete'],
            'finance_payments' => ['view', 'create', 'update', 'delete'],
            'finance_reports' => ['view'],
            'students' => ['view', 'update'],
            'registrations' => ['view'],
            'institution_docs' => ['view'],
            'calendar' => ['view'],
        ],

        'qa_officer' => [
            'programmes' => ['view'],
            'semesters' => ['view'],
            'courses' => ['view'],
            'registrations' => ['view'],
            'results' => ['view'],
            'exams' => ['view'],
            'students' => ['view'],
            'finance_reports' => ['view'],
            'institution_docs' => ['view'],
            'calendar' => ['view'],
        ],

        'tutor_staff' => [
            'courses' => ['view'],
            'results' => ['view', 'update'],
            'exams' => ['view'],
            'timetable' => ['view', 'create', 'update'],
            'question_bank' => ['view', 'create', 'update'],
            'clinical' => ['view', 'create', 'update'],
            'students' => ['view'],
            'registrations' => ['view'],
            'college_comms' => ['view'],
            'student_affairs' => ['view'],
            'accommodation' => ['view'],
            'calendar' => ['view'],
            'institution_docs' => ['view'],
        ],

        'clinical_instructor' => [
            'clinical' => ['view', 'update'],
            'students' => ['view'],
            'finance_reports' => ['view'],
        ],
    ],

    /** Modules counted as “academic portfolio” for legacy helpers. */
    'academic_modules' => [
        'programmes', 'semesters', 'courses', 'registrations', 'results',
        'exams', 'timetable', 'question_bank', 'clinical',
    ],

    'finance_modules' => [
        'finance_fees', 'finance_payments', 'finance_reports', 'inventory',
    ],

    /** Positions with academic portfolio (programmes → clinical). */
    'academic_portfolio_roles' => [
        'vice_principal_arc',
        'hod_cmt',
        'hod_mlt',
        'admission_officer',
        'examination_officer',
        'tutor_staff',
        'clinical_instructor',
        'qa_officer',
    ],

    /** Positions with finance / procurement portfolio. */
    'finance_portfolio_roles' => [
        'vice_principal_afp',
        'procurement_officer',
        'accountant',
        'secretary',
        'accommodation_matron',
    ],
];

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script>
    (function(){try{var t=localStorage.getItem('cohas-theme');if(t!=='light'&&t!=='dark'){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();
    </script>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    @auth
    <link href="{{ asset('vendor/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-select-search.css') }}" rel="stylesheet">
    @endauth
    <link href="{{ asset('css/cohas-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-brand.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-app-shell.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-customizer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-fonts.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    @include('layouts.partials.cohas-page-loader')
    @auth
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" data-bs-theme="dark" aria-label="Main navigation">
      <div class="container-fluid">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" width="30" height="30" class="rounded-circle bg-white p-1">
            <span>{{ config('app.name') }}</span>
        </a>
        <button class="navbar-toggler" type="button" id="sidebarToggleMobile" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-house-door"></i> {{ __('ui.nav.dashboard') }}</a></li>
            @if(auth()->user()->isGuardian())
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('parent.portal') ? 'active' : '' }}" href="{{ route('parent.portal') }}">
                <i class="bi bi-people"></i> Parent portal</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('college-documents.index') }}"><i class="bi bi-folder2-open"></i> College documents</a></li>
            @if(config('college.integrations.moodle_url'))
            <li class="nav-item"><a class="nav-link" href="{{ config('college.integrations.moodle_url') }}" target="_blank" rel="noopener"><i class="bi bi-mortarboard"></i> Moodle <i class="bi bi-box-arrow-up-right small"></i></a></li>
            @endif
            @elseif(auth()->user()->isStudent())
            @php $me = auth()->user()->student; @endphp
            @if($me)
            @php
                $studentAcademicsActive = request()->routeIs(
                    'my.modules',
                    'my.module-registration',
                    'my.timetable',
                    'my.assessments',
                    'my.module-results',
                    'results.portal',
                    'results.transcript*'
                );
                $assessmentsActive = request()->routeIs('my.assessments');
                $moduleResultsActive = request()->routeIs('my.module-results');
                $timetableActive = request()->routeIs('my.timetable');
            @endphp
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('students.show') && request()->route('student')?->id === $me->id ? 'active' : '' }}" href="{{ route('students.show', $me) }}">
                <i class="bi bi-mortarboard"></i> Graduation</a></li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ $studentAcademicsActive ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-journal-bookmark"></i> Academics</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('my.module-registration*') ? 'active' : '' }}" href="{{ route('my.module-registration') }}">Registered modules</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('my.modules') ? 'active' : '' }}" href="{{ route('my.modules') }}">My Modules Detail</a></li>
                    <li><a class="dropdown-item {{ $assessmentsActive ? 'active' : '' }}" href="{{ route('my.assessments') }}">My Assessments</a></li>
                    <li><a class="dropdown-item {{ $moduleResultsActive ? 'active' : '' }}" href="{{ route('my.module-results') }}">My Modules Result</a></li>
                    <li><a class="dropdown-item {{ $timetableActive ? 'active' : '' }}" href="{{ route('my.timetable') }}">My Class Timetable</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('my.registrations') ? 'active' : '' }}" href="{{ route('my.registrations') }}">
                <i class="bi bi-ui-checks-grid"></i> Online registration</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('college-documents.*') ? 'active' : '' }}" href="{{ route('college-documents.index') }}">
                <i class="bi bi-folder2-open"></i> College documents</a></li>
            @php $studentFinanceActive = request()->routeIs('students.ledger'); @endphp
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ $studentFinanceActive ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-wallet2"></i> {{ __('ui.nav.finance') }}</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ $studentFinanceActive ? 'active' : '' }}" href="{{ route('students.ledger', $me) }}">Financial Statement</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('my.accommodation') ? 'active' : '' }}" href="{{ route('my.accommodation') }}">
                <i class="bi bi-building"></i> Accommodation</a></li>
            @php $studentClinicalActive = request()->routeIs('my.clinical.*'); @endphp
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ $studentClinicalActive ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-hospital"></i> Clinical training</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('my.clinical.placement') ? 'active' : '' }}" href="{{ route('my.clinical.placement') }}">My placement</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('my.clinical.logbook*') ? 'active' : '' }}" href="{{ route('my.clinical.logbook.index') }}">Clinical logbook</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('my.clinical.remediation') ? 'active' : '' }}" href="{{ route('my.clinical.remediation') }}">Remediation</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('my.exams') ? 'active' : '' }}" href="{{ route('my.exams') }}">
                <i class="bi bi-ticket-perforated"></i> Examination ticket</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}">
                <i class="bi bi-calendar3"></i> College calendar</a></li>
            @else
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('profile.complete*') ? 'active' : '' }}" href="{{ route('profile.complete') }}">
                <i class="bi bi-person-lines-fill"></i> Complete Profile</a></li>
            @endif
            @else
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('office-documents.*') ? 'active' : '' }}" href="{{ route('office-documents.index') }}">
                <i class="bi bi-send-check"></i> e-Office</a></li>
            @php
                $showAcademicMenu = auth()->user()->canModule('programmes', 'view')
                    || auth()->user()->canModule('semesters', 'view')
                    || auth()->user()->canModule('courses', 'view')
                    || auth()->user()->canModule('registrations', 'view')
                    || auth()->user()->canModule('results', 'view')
                    || auth()->user()->canModule('question_bank', 'view')
                    || auth()->user()->canModule('exams', 'view')
                    || auth()->user()->canModule('timetable', 'view')
                    || auth()->user()->canModule('clinical', 'view')
                    || auth()->user()->canModule('student_attendance', 'view');
            @endphp
            @if($showAcademicMenu)
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('programmes.*', 'semesters.*', 'courses.*', 'semester-registrations.*', 'registration-wizard.*', 'results.*', 'exam-slots.*', 'timetable-slots.*', 'assessment-studio.*', 'clinical-rotations.*', 'clinical-procedures.*', 'clinical-logbook.*', 'clinical.framework', 'student-attendance.*', 'reports.class-list', 'reports.academic-standing', 'reports.nactvet*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-mortarboard-fill"></i> {{ __('ui.nav.academics') }}</a>
                    <ul class="dropdown-menu">
                    @if(auth()->user()->canModule('programmes', 'view') || auth()->user()->canModule('semesters', 'view'))
                    <li><h6 class="dropdown-header">Programme &amp; terms</h6></li>
                    @endif
                    @canModule('programmes', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('programmes.*') ? 'active' : '' }}" href="{{ route('programmes.index') }}"><i class="bi bi-collection"></i> Programmes</a></li>
                    @endcanModule
                    @canModule('semesters', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('semesters.*') ? 'active' : '' }}" href="{{ route('semesters.index') }}"><i class="bi bi-calendar-range"></i> Semesters</a></li>
                    @endcanModule
                    @canModule('courses', 'view')
                    <li><h6 class="dropdown-header">Module catalogue</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('courses.*') ? 'active' : '' }}" href="{{ route('courses.index') }}"><i class="bi bi-diagram-3"></i> Module catalogue</a></li>
                    @endcanModule
                    @canModule('registrations', 'view')
                    <li><h6 class="dropdown-header">Registration</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('semester-registrations.*', 'registration-wizard.*') ? 'active' : '' }}" href="{{ route('semester-registrations.index') }}"><i class="bi bi-ui-checks-grid"></i> Student registration</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('results', 'view') || auth()->user()->canModule('question_bank', 'view'))
                    <li><h6 class="dropdown-header">Marks &amp; exams</h6></li>
                    @endif
                    @canModule('results', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('results.*') ? 'active' : '' }}" href="{{ route('results.index') }}"><i class="bi bi-journal-text"></i> Results</a></li>
                    @endcanModule
                    @canModule('question_bank', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('assessment-studio.*') ? 'active' : '' }}" href="{{ route('assessment-studio.index') }}"><i class="bi bi-clipboard2-check"></i> Assessment Studio</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('exams', 'view') || auth()->user()->canModule('timetable', 'view'))
                    <li><h6 class="dropdown-header">Timetables</h6></li>
                    @endif
                    @canModule('exams', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('exam-slots.*') ? 'active' : '' }}" href="{{ route('exam-slots.index') }}"><i class="bi bi-calendar-event"></i> Exam schedule</a></li>
                    @endcanModule
                    @canModule('timetable', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('timetable-slots.*') ? 'active' : '' }}" href="{{ route('timetable-slots.index') }}"><i class="bi bi-clock-history"></i> Class schedule</a></li>
                    @endcanModule
                    @canModule('clinical', 'view')
                    <li><h6 class="dropdown-header">Clinical training</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('clinical-rotations.*') ? 'active' : '' }}" href="{{ route('clinical-rotations.index') }}"><i class="bi bi-hospital"></i> Rotation rounds</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('clinical-logbook.*', 'clinical.framework') ? 'active' : '' }}" href="{{ route('clinical-logbook.index') }}"><i class="bi bi-journal-medical"></i> Logbook review</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('clinical-procedures.*') ? 'active' : '' }}" href="{{ route('clinical-procedures.index') }}"><i class="bi bi-list-check"></i> Procedures catalogue</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('clinical.coordinator', 'clinical.reports', 'clinical.progression.*') ? 'active' : '' }}" href="{{ route('clinical.coordinator') }}"><i class="bi bi-speedometer2"></i> Coordinator dashboard</a></li>
                    @endcanModule
                    @canModule('student_attendance', 'view')
                    <li><h6 class="dropdown-header">Attendance</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('student-attendance.*') ? 'active' : '' }}" href="{{ route('student-attendance.index') }}"><i class="bi bi-fingerprint"></i> Student attendance</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('finance_reports', 'view') || auth()->user()->canModule('results', 'view'))
                    <li><h6 class="dropdown-header">Reports</h6></li>
                    @endif
                    @canModule('finance_reports', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('reports.class-list') ? 'active' : '' }}" href="{{ route('reports.class-list') }}"><i class="bi bi-journal-text"></i> Class list</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('reports.nactvet*') ? 'active' : '' }}" href="{{ route('reports.nactvet-hub') }}"><i class="bi bi-building"></i> NACTVET reporting pack</a></li>
                    @endcanModule
                    @canModule('results', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('reports.academic-standing') ? 'active' : '' }}" href="{{ route('reports.academic-standing') }}"><i class="bi bi-award"></i> Academic standing</a></li>
                    @endcanModule
                </ul>
            </li>
            @endif
            @php
                $showFinanceMenu = auth()->user()->canModule('finance_fees', 'view')
                    || auth()->user()->canModule('finance_payments', 'view')
                    || auth()->user()->canModule('finance_reports', 'view');
            @endphp
            @if($showFinanceMenu)
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('fee-structures.*', 'payments.*', 'payment-instalments.*', 'reports.index', 'reports.income', 'reports.arrears', 'reports.payment-by-programme', 'reports.fee-collection-summary', 'reports.enrollment') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-currency-exchange"></i> {{ __('ui.nav.finance') }}</a>
                    <ul class="dropdown-menu">
                    @canModule('finance_fees', 'view')
                    <li><h6 class="dropdown-header">Fees &amp; setup</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('fee-structures.*') ? 'active' : '' }}" href="{{ route('fee-structures.index') }}"><i class="bi bi-table"></i> Fees</a></li>
                    @endcanModule
                    @canModule('finance_payments', 'view')
                    <li><h6 class="dropdown-header">Payments</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-clock-history"></i> Payment history</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('payment-instalments.*') ? 'active' : '' }}" href="{{ route('payment-instalments.index') }}"><i class="bi bi-calendar2-check"></i> Payment instalments</a></li>
                    @endcanModule
                    @canModule('finance_reports', 'view')
                    <li><h6 class="dropdown-header">Reports</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-graph-up"></i> Financial reports</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('reports.payment-by-programme') ? 'active' : '' }}" href="{{ route('reports.payment-by-programme') }}"><i class="bi bi-pie-chart"></i> Payments by programme</a></li>
                    @endcanModule
                </ul>
            </li>
            @endif
            @canModule('accountancy', 'view')
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('departments.*', 'cash-accounts.*', 'budget-lines.*', 'creditors.*', 'institution-transactions.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bank"></i> Accountancy</a>
                    <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('cash-accounts.*') ? 'active' : '' }}" href="{{ route('cash-accounts.index') }}"><i class="bi bi-wallet2"></i> Cash accounts</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('institution-transactions.*') ? 'active' : '' }}" href="{{ route('institution-transactions.index') }}"><i class="bi bi-journal-text"></i> Payments &amp; deposits ledger</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('creditors.*') ? 'active' : '' }}" href="{{ route('creditors.index') }}"><i class="bi bi-file-earmark-ruled"></i> Creditors</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('budget-lines.*') ? 'active' : '' }}" href="{{ route('budget-lines.index') }}"><i class="bi bi-pie-chart"></i> Department budgets</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-diagram-3"></i> Departments</a></li>
                </ul>
            </li>
            @endcanModule
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('students.*', 'message-logs.*', 'announcements.*', 'leave-applications.*', 'graduation-clearances.*', 'conduct-records.*', 'transcript-requests.*', 'certificate-collections.*', 'student-card-status.*', 'calendar.*', 'reports.admission-control-sheet*', 'reports.students-on-leave', 'reports.graduation-clearance') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-briefcase"></i> Registrar's office</a>
                    <ul class="dropdown-menu">
                    @canModule('students', 'view')
                    <li><h6 class="dropdown-header">People</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><i class="bi bi-people"></i> Students</a></li>
                    @endcanModule
                    @canModule('college_comms', 'view')
                    <li><h6 class="dropdown-header">Communication</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('message-logs.*') ? 'active' : '' }}" href="{{ route('message-logs.index') }}"><i class="bi bi-chat-dots"></i> Message log</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}"><i class="bi bi-megaphone"></i> Announcements</a></li>
                    @endcanModule
                    @canModule('staff_leave', 'view')
                    <li><h6 class="dropdown-header">Staff leave</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('leave-applications.*') ? 'active' : '' }}" href="{{ route('leave-applications.index') }}"><i class="bi bi-calendar-x"></i> Leave applications</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('student_affairs', 'view') || auth()->user()->canModule('conduct_records', 'view'))
                    <li><h6 class="dropdown-header">Student affairs</h6></li>
                    @endif
                    @canModule('student_affairs', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('graduation-clearances.*') ? 'active' : '' }}" href="{{ route('graduation-clearances.index') }}"><i class="bi bi-clipboard-check"></i> Graduation clearance</a></li>
                    @endcanModule
                    @canModule('conduct_records', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('conduct-records.*') ? 'active' : '' }}" href="{{ route('conduct-records.index') }}"><i class="bi bi-shield-exclamation"></i> Student Conduct &amp; Permits</a></li>
                    @endcanModule
                    @canModule('transcript_requests', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('transcript-requests.*') ? 'active' : '' }}" href="{{ route('transcript-requests.index') }}"><i class="bi bi-file-earmark-text"></i> Transcript requests</a></li>
                    @endcanModule
                    @canModule('certificate_collection', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('certificate-collections.*') ? 'active' : '' }}" href="{{ route('certificate-collections.index') }}"><i class="bi bi-patch-check"></i> Certificate collection</a></li>
                    @endcanModule
                    @canModule('student_card_status', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('student-card-status.*') ? 'active' : '' }}" href="{{ route('student-card-status.index') }}"><i class="bi bi-person-vcard"></i> Student ID &amp; NHIF status</a></li>
                    @endcanModule
                    @canModule('institution_docs', 'view')
                    <li><h6 class="dropdown-header">Records</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('institution-documents.*') ? 'active' : '' }}" href="{{ route('institution-documents.index') }}"><i class="bi bi-folder2-open"></i> Institution documents</a></li>
                    @endcanModule
                    @canModule('calendar', 'view')
                    <li><h6 class="dropdown-header">Planning</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}"><i class="bi bi-calendar3"></i> College calendar</a></li>
                    @endcanModule
                    @if(auth()->user()->canModule('finance_reports', 'view') || auth()->user()->canModule('staff_leave', 'view') || auth()->user()->canModule('student_affairs', 'view'))
                    <li><h6 class="dropdown-header">Reports</h6></li>
                    @endif
                    @canModule('finance_reports', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('reports.admission-control-sheet*') ? 'active' : '' }}" href="{{ route('reports.admission-control-sheet') }}"><i class="bi bi-clipboard2-data"></i> Admission control sheet</a></li>
                    @endcanModule
                    @canModule('staff_leave', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('reports.students-on-leave') ? 'active' : '' }}" href="{{ route('reports.students-on-leave') }}"><i class="bi bi-calendar-x"></i> Students on leave</a></li>
                    @endcanModule
                    @canModule('student_affairs', 'view')
                    <li><a class="dropdown-item {{ request()->routeIs('reports.graduation-clearance') ? 'active' : '' }}" href="{{ route('reports.graduation-clearance') }}"><i class="bi bi-clipboard-check"></i> Graduation clearance report</a></li>
                    @endcanModule
                </ul>
            </li>
            @if(auth()->user()->canModule('accommodation_facilities', 'view') || auth()->user()->canModule('accommodation', 'view'))
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('hostels.*', 'rooms.*', 'rooms.occupancy*', 'accommodation-allocations.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-building"></i> Accommodation</a>
                    <ul class="dropdown-menu">
                    @canModule('accommodation_facilities', 'view')
                    <li><h6 class="dropdown-header">Buildings</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('hostels.*') ? 'active' : '' }}" href="{{ route('hostels.index') }}"><i class="bi bi-building"></i> Hostels</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('rooms.index', 'rooms.create', 'rooms.edit') ? 'active' : '' }}" href="{{ route('rooms.index') }}"><i class="bi bi-door-open"></i> Rooms</a></li>
                    <li><h6 class="dropdown-header">Occupancy</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('rooms.occupancy*') ? 'active' : '' }}" href="{{ route('rooms.occupancy') }}"><i class="bi bi-people"></i> Live by room</a></li>
                    @endcanModule
                    @canModule('accommodation', 'view')
                    <li><h6 class="dropdown-header">Allocations</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('accommodation-allocations.*') ? 'active' : '' }}" href="{{ route('accommodation-allocations.index') }}"><i class="bi bi-person-badge"></i> Allocations</a></li>
                    @endcanModule
                </ul>
            </li>
            @endcanModule
            @canModule('inventory', 'view')
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('inventory-items.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam"></i> Inventory</a>
                    <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Institution</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('inventory-items.*') ? 'active' : '' }}" href="{{ route('inventory-items.index') }}"><i class="bi bi-clipboard-data"></i> Items &amp; assets</a></li>
                </ul>
            </li>
            @endcanModule
            @php
                $isSystemAdmin = auth()->user()->isAdmin();
                $hasSystemView = $isSystemAdmin || auth()->user()->canModule('system', 'view');
            @endphp
            @if($hasSystemView)
                            <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('users.*', 'activity-log*', 'export*', 'users.role-permissions', 'trash.*', 'maintenance.*', 'profile-lock.*', 'student-import-settings.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> System</a>
                    <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Access</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('users.index', 'users.create', 'users.edit') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-gear"></i> Users</a></li>
                    @if($isSystemAdmin)
                    <li><a class="dropdown-item {{ request()->routeIs('users.role-permissions') ? 'active' : '' }}" href="{{ route('users.role-permissions') }}"><i class="bi bi-shield-lock"></i> Role permissions</a></li>
                    @endif
                    <li><h6 class="dropdown-header">Audit &amp; data</h6></li>
                    <li><a class="dropdown-item {{ request()->routeIs('activity-log*') ? 'active' : '' }}" href="{{ route('activity-log.index') }}"><i class="bi bi-journal-text"></i> Activity log</a></li>
                    @if($isSystemAdmin)
                    <li><a class="dropdown-item {{ request()->routeIs('export*') ? 'active' : '' }}" href="{{ route('export.index') }}"><i class="bi bi-download"></i> Export data</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('integrations.*') ? 'active' : '' }}" href="{{ route('integrations.index') }}"><i class="bi bi-plug"></i> Integrations</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('trash.*') ? 'active' : '' }}" href="{{ route('trash.index') }}"><i class="bi bi-trash3"></i> Trash</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" href="{{ route('maintenance.edit') }}"><i class="bi bi-cone-striped"></i> Maintenance mode</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('profile-lock.*') ? 'active' : '' }}" href="{{ route('profile-lock.edit') }}"><i class="bi bi-person-lock"></i> Profile edit lock</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('student-import-settings.*') ? 'active' : '' }}" href="{{ route('student-import-settings.edit') }}"><i class="bi bi-sliders"></i> Student import settings</a></li>
                    @endif
                </ul>
            </li>
            @endif
            @endif
        </ul>
        @php
            $currentUser = auth()->user();
            $studentRecord = $currentUser->student;
            $unreadNotificationsCount = $currentUser->unreadNotifications()->count();
            $topbarNotifications = $currentUser->notifications()->latest()->limit(6)->get();
        @endphp
        <ul class="navbar-nav align-items-lg-center gap-lg-2">
            @unless($currentUser->isStudent() || $currentUser->isGuardian())
            <li class="nav-item">
                <form method="GET" action="{{ route('search.index') }}" class="topbar-search d-none d-md-flex" role="search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" class="topbar-search-input" placeholder="Search students…" aria-label="Search students" value="{{ request('q') }}">
                </form>
            </li>
            @endunless
            <li class="nav-item d-none d-md-flex align-items-center">
                @if($studentRecord)
                    <span class="login-as">{{ __('ui.nav.login_as') }} <strong class="topbar-reg-no">{{ $studentRecord->registrationNumberDisplay() ?: $studentRecord->full_name }}</strong></span>
                @elseif($currentUser->staff_id)
                    <span class="topbar-reg-no">ID: {{ $currentUser->staff_id }}</span>
                @else
                    <span class="login-as">Login as: {{ $currentUser->staffDisplayName() ?: $currentUser->email }}</span>
                @endif
            </li>
            <li class="nav-item d-none d-md-block topbar-datetime" id="topbarDateTime" aria-live="polite">
                <span id="topbarDate"></span> &middot; <span id="topbarTime"></span>
            </li>
            <li class="nav-item">
            <div class="notif-dropdown">
                <button type="button" class="topbar-bell" id="notifToggle" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotificationsCount > 0)
                        <span class="notif-badge bg-danger text-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
                    @endif
                </button>
                <div class="notif-menu" id="notifMenu">
                    <div class="notif-menu-header">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0">Mark all read</button>
                        </form>
                    </div>
                    <div class="notif-list">
                        @forelse($topbarNotifications as $n)
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                @csrf
                                <button type="submit" class="notif-item w-100 text-start border-0 bg-transparent">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="notif-item-title">{{ $n->data['title'] ?? 'Notification' }}</span>
                                        @if(!$n->read_at)<span class="badge bg-primary">New</span>@endif
                                    </div>
                                    <div class="notif-item-msg">{{ $n->data['message'] ?? '' }}</div>
                                    <div class="small text-muted mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                                </button>
                            </form>
                        @empty
                            <div class="p-3 text-muted small">No notifications yet.</div>
                        @endforelse
                    </div>
                    <div class="notif-menu-footer">
                        <a href="{{ route('notifications.index') }}" class="small text-decoration-none">View all notifications</a>
                    </div>
                </div>
            </div>
            </li>
            <li class="nav-item d-flex align-items-center">@include('layouts.partials.customizer-toggle')</li>
            <li class="nav-item d-flex align-items-center">@include('layouts.partials.language-toggle')</li>
            <li class="nav-item">
            <div class="profile-dropdown">
                <button type="button" class="topbar-avatar" id="profileToggle" aria-label="Profile menu" aria-expanded="false" aria-haspopup="true">
                    @if($currentUser->profile_photo_url)
                        <img src="{{ $currentUser->profile_photo_url }}" alt="">
                    @else
                        <span>{{ $currentUser->initials }}</span>
                    @endif
                </button>
                <div class="profile-menu" id="profileMenu" role="menu">
                    <div class="profile-menu-header">
                        <strong>{{ $currentUser->name }}</strong>
                        <small>{{ $studentRecord ? ($studentRecord->registrationNumberDisplay() ?: $studentRecord->full_name) : $currentUser->email }}</small>
                    </div>
                    @if($studentRecord && $studentRecord->idCardReady())
                    <a href="{{ route('students.id-card', $studentRecord) }}" target="_blank" class="profile-menu-item w-100 border-0 d-block text-decoration-none" role="menuitem">
                        <i class="bi bi-person-vcard me-2"></i>My ID card
                    </a>
                    @endif
                    @unless($currentUser->isStudent())
                    <a href="{{ route('profile.complete') }}" class="profile-menu-item w-100 border-0 d-block text-decoration-none" role="menuitem">
                        <i class="bi bi-person-gear me-2"></i>My Profile
                    </a>
                    @endunless
                    <button type="button" class="profile-menu-item w-100 border-0" role="menuitem" data-bs-toggle="modal" data-bs-target="#profilePhotoModal">
                        <i class="bi bi-camera me-2"></i>Change photo
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form-topbar">
                        @csrf
                        <button type="button" class="profile-menu-item w-100 border-0" role="menuitem">
                            <i class="bi bi-box-arrow-left me-2"></i>{{ __('ui.nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>
            </li>
        </ul>
        </div>
        </div>
    </nav>
    <div class="modal fade" id="profilePhotoModal" tabindex="-1" aria-labelledby="profilePhotoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="profilePhotoModalLabel"><i class="bi bi-camera me-2"></i>Profile photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body text-center">
                                <div class="mb-3">
                                    @if($currentUser->profile_photo_url)
                                        <img src="{{ $currentUser->profile_photo_url }}" alt="" id="profilePhotoPreview" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                                    @else
                                        <div id="profilePhotoPreviewWrap" style="width:120px;height:120px;border-radius:50%;background:#0d3651;color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto;">{{ $currentUser->initials }}</div>
                                        <img src="" alt="" id="profilePhotoPreview" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;display:none;">
                                    @endif
                                </div>
                                <input type="file" name="photo" id="profilePhotoInput" class="form-control" accept="image/jpeg,image/png" required>
                                <div class="form-text">JPG or PNG, up to 2MB, at least 200x200px. For the best fit on your profile and ID card, use a square or portrait passport-style photo with your face centered.</div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                @if($currentUser->profile_photo_url)
                                <button type="submit" form="removePhotoForm" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Remove photo</button>
                                @else
                                <span></span>
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload</button>
                            </div>
                        </form>
                        @if($currentUser->profile_photo_url)
                        <form method="POST" action="{{ route('profile.photo.destroy') }}" id="removePhotoForm" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @push('scripts')
            <script>
            (function () {
                var input = document.getElementById('profilePhotoInput');
                var preview = document.getElementById('profilePhotoPreview');
                var initialsWrap = document.getElementById('profilePhotoPreviewWrap');
                if (!input || !preview) return;
                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!file) return;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        preview.style.display = 'inline-block';
                        if (initialsWrap) initialsWrap.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                });
            })();
            </script>
            @endpush
        <main class="main-content" id="main-content">
            @yield('content')
        </main>
        <footer class="app-footer" role="contentinfo">
            <p>© {{ now()->year }} {{ config('app.name') }}. {{ __('ui.footer.rights') }} {{ __('ui.footer.powered') }} <span class="app-footer-version">· v{{ config('app.version') }}</span></p>
        </footer>
    @include('layouts.partials.customizer')
    @else
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('home') }}"><span class="logo-circle navbar-logo me-2"><img src="{{ asset('images/logo.png') }}" alt=""></span>{{ config('app.name') }}</a>
            <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
                @include('layouts.partials.theme-toggle')
                @include('layouts.partials.language-toggle')
                <a class="nav-link" href="{{ route('login.create') }}">{{ __('ui.nav.login') }}</a>
            </div>
        </div>
    </nav>
    <main class="container flex-grow-1 py-4">
        @yield('content')
    </main>
    <footer class="border-top bg-light py-3 mt-auto" role="contentinfo">
        <div class="container small text-center text-muted">
            <p class="mb-0">© {{ now()->year }} {{ config('app.name') }}. {{ __('ui.footer.rights') }} {{ __('ui.footer.powered') }} <span class="app-footer-version">· v{{ config('app.version') }}</span></p>
        </div>
    </footer>
    @endauth

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/cohas-theme.js') }}"></script>
    @auth
    <script src="{{ asset('js/cohas-customizer.js') }}"></script>
    <script src="{{ asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>
    <script src="{{ asset('js/cohas-select-search.js') }}"></script>
    @endauth
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alert-to-swal.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isAuthenticated = @json(auth()->check());
            var idleLogoutMinutes = Number(@json((int) env('IDLE_LOGOUT_MINUTES', 20)));
            var idlePromptSeconds = Number(@json((int) env('IDLE_PROMPT_SECONDS', 60)));
            var logoutUrl = @json(route('logout'));
            var loginUrl = @json(route('login.create'));

            function autoLogoutNow() {
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch(logoutUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: '{}',
                    credentials: 'same-origin',
                }).finally(function() {
                    window.location.href = loginUrl + '?expired=1';
                });
            }

            if (isAuthenticated && idleLogoutMinutes > 0) {
                var idleTimer = null;
                var countdownTimer = null;
                var warningOpen = false;
                var activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'];

                function scheduleIdleWarning() {
                    if (idleTimer) clearTimeout(idleTimer);
                    idleTimer = setTimeout(showIdleWarning, idleLogoutMinutes * 60 * 1000);
                }

                function showIdleWarning() {
                    warningOpen = true;
                    var remaining = Math.max(1, idlePromptSeconds);
                    Swal.fire({
                        title: 'Session expiring',
                        html: 'You have been inactive. Continue session? <br><small>Auto logout in <strong id="idleCountdown">'+remaining+'</strong>s</small>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Logout',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: remaining * 1000,
                        timerProgressBar: true,
                        didOpen: function() {
                            countdownTimer = setInterval(function() {
                                remaining -= 1;
                                var el = document.getElementById('idleCountdown');
                                if (el) el.textContent = String(Math.max(remaining, 0));
                            }, 1000);
                        },
                        willClose: function() {
                            if (countdownTimer) clearInterval(countdownTimer);
                        },
                    }).then(function(result) {
                        warningOpen = false;
                        if (result.isConfirmed) {
                            scheduleIdleWarning();
                        } else {
                            autoLogoutNow();
                        }
                    });
                }

                activityEvents.forEach(function(evt) {
                    document.addEventListener(evt, function() {
                        if (!warningOpen) scheduleIdleWarning();
                    }, { passive: true });
                });

                scheduleIdleWarning();
            }

            function updateDateTime() {
                var d = new Date();
                var dateEl = document.getElementById('topbarDate');
                var timeEl = document.getElementById('topbarTime');
                var dateLocale = (document.documentElement.lang || 'en').startsWith('sw') ? 'sw-TZ' : 'en-GB';
                if (dateEl) dateEl.textContent = d.toLocaleDateString(dateLocale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                if (timeEl) timeEl.textContent = d.toLocaleTimeString(dateLocale, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            updateDateTime();
            if (setInterval) setInterval(updateDateTime, 1000);
            var profileToggle = document.getElementById('profileToggle');
            var profileMenu = document.getElementById('profileMenu');
            var notifToggle = document.getElementById('notifToggle');
            var notifMenu = document.getElementById('notifMenu');
            if (profileToggle && profileMenu) {
                profileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('show');
                    if (notifMenu) notifMenu.classList.remove('show');
                    profileToggle.setAttribute('aria-expanded', profileMenu.classList.contains('show'));
                });
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('show');
                    if (notifMenu) notifMenu.classList.remove('show');
                    profileToggle.setAttribute('aria-expanded', 'false');
                    if (notifToggle) notifToggle.setAttribute('aria-expanded', 'false');
                });
                profileMenu.addEventListener('click', function(e) { e.stopPropagation(); });
            }
            if (notifToggle && notifMenu) {
                notifToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifMenu.classList.toggle('show');
                    if (profileMenu) profileMenu.classList.remove('show');
                    notifToggle.setAttribute('aria-expanded', notifMenu.classList.contains('show'));
                });
                notifMenu.addEventListener('click', function(e) { e.stopPropagation(); });
            }
            document.querySelectorAll('.logout-form-topbar').forEach(function(f) {
                f.querySelector('button')?.addEventListener('click', function() {
                    Swal.fire({ title: 'Logout?', text: 'You will be signed out.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) f.submit(); });
                });
            });
            var sidebar = document.getElementById('sidebar');
            var mainWrap = document.getElementById('mainWrap');
            var toggle = document.getElementById('sidebarToggle');
            var toggleMobile = document.getElementById('sidebarToggleMobile');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && mainWrap) {
                var collapsed = localStorage.getItem('sidebarCollapsed') === '1';
                if (collapsed) { sidebar.classList.add('collapsed'); mainWrap.classList.remove('expanded'); mainWrap.classList.add('collapsed'); }
                if (toggle) {
                    var icon = document.getElementById('sidebarToggleIcon');
                    toggle.addEventListener('click', function() {
                        sidebar.classList.toggle('collapsed');
                        mainWrap.classList.toggle('expanded'); mainWrap.classList.toggle('collapsed');
                        if (icon) icon.className = mainWrap.classList.contains('collapsed') ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar-inset-reverse';
                        localStorage.setItem('sidebarCollapsed', mainWrap.classList.contains('collapsed') ? '1' : '0');
                    });
                    if (collapsed && icon) icon.className = 'bi bi-layout-sidebar-inset';
                }
                if (toggleMobile && overlay) {
                    toggleMobile.addEventListener('click', function() { sidebar.classList.add('show'); overlay.style.display = 'block'; });
                    overlay.addEventListener('click', function() { sidebar.classList.remove('show'); overlay.style.display = 'none'; });
                }
            }
            document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var group = btn.closest('.nav-group');
                    if (group) group.classList.toggle('expanded');
                });
            });
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) {
                new bootstrap.Popover(el);
            });
            document.addEventListener('click', function(e) {
                document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) {
                    if (el !== e.target && !el.contains(e.target)) {
                        var instance = bootstrap.Popover.getInstance(el);
                        if (instance) instance.hide();
                    }
                });
            });
            document.querySelectorAll('.logout-form').forEach(function(f) {
                f.querySelector('button')?.addEventListener('click', function() {
                    Swal.fire({ title: 'Logout?', text: 'You will be signed out.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) f.submit(); });
                });
            });
            document.querySelectorAll('[data-swal-confirm]').forEach(function(el) {
                var form = el.closest('form') || el.form;
                if (!form) return;
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    var title = el.getAttribute('data-swal-title') || 'Are you sure?';
                    var text = el.getAttribute('data-swal-text') || '';
                    var icon = el.getAttribute('data-swal-icon') || 'warning';
                    Swal.fire({ title: title, text: text, icon: icon, showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d' })
                        .then(function(r) { if (r.isConfirmed) form.submit(); });
                });
            });
            var success = @json(session('success'));
            var error = @json(session('error'));
            var warning = @json(session('warning'));
            var info = @json(session('info'));
            if (success) Swal.fire({ icon: 'success', title: 'Success', text: success, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (info) Swal.fire({ icon: 'info', title: 'Notice', text: info, timer: 4000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (warning) Swal.fire({ icon: 'warning', title: 'Notice', text: warning, timer: 4000, showConfirmButton: false, toast: true, position: 'top-end' });
            if (error) Swal.fire({ icon: 'error', title: 'Error', text: error });
            @if($errors->any())
            Swal.fire({ icon: 'error', title: 'Validation', html: {!! json_encode(implode('<br>', $errors->all())) !!} });
            @endif
        });
    </script>
    @include('layouts.partials.cohas-loader-script')
    @stack('scripts')
</body>
</html>

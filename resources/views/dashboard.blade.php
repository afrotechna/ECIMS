@extends('layouts.app')

@section('title', __('ui.dashboard.title'))

@section('content')
@if(!auth()->user()->isStudent())
@php
    $canViewPayments = auth()->user()->canModule('finance_payments', 'view');
    $canViewResults = auth()->user()->canModule('results', 'view');
    $canViewRegistrations = auth()->user()->canModule('registrations', 'view');
    $canCreateRegistrations = auth()->user()->canModule('registrations', 'create');
    $canCreateProgrammes = auth()->user()->canModule('programmes', 'create');
    $canCreateFeeStructures = auth()->user()->canModule('finance_fees', 'create');
    $canViewFinanceArea = $canViewPayments || auth()->user()->canModule('finance_reports', 'view') || auth()->user()->canModule('finance_fees', 'view');
    $canSeeAcademicActions = $canViewResults || $canViewRegistrations;
@endphp
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title-landing mb-1">{{ __('ui.dashboard.title') }}</h1>
        <p class="page-subtitle-landing mb-0">
            {{ __('ui.dashboard.welcome') }}{{ auth()->user()->isStudent() && auth()->user()->student ? ', ' . auth()->user()->student->first_name : '' }}.
            @if(auth()->user()->isStudent() && ($studentRegistrationBadge ?? null))
                <span class="badge {{ $studentRegistrationBadge['class'] }} ms-1">{{ $studentRegistrationBadge['text'] }}</span>
            @elseif(! auth()->user()->isStudent())
                <span class="badge bg-light text-dark ms-1">{{ \App\Models\User::roleLabel($userRole ?? '') }}</span>
            @endif
        </p>
    </div>
    @if(!auth()->user()->isStudent())
    <div class="d-flex gap-2 flex-wrap">
        @if($canViewPayments)
        <a href="{{ route('payments.index') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-clock-history me-1"></i>{{ __('ui.dashboard.payment_history') }}</a>
        @endif
        @if($canViewResults)
        <a href="{{ route('results.index') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-journal-check me-1"></i>{{ __('ui.dashboard.results_import') }}</a>
        @endif
        @if($canViewRegistrations)
        <a href="{{ route('semester-registrations.index') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-ui-checks-grid me-1"></i>{{ __('ui.dashboard.student_registration') }}</a>
        @endif
    </div>
    @endif
    @if(!auth()->user()->isStudent() && ($canViewFinanceArea || $canSeeAcademicActions))
    <ul class="nav nav-pills nav-fill gap-1 p-1 bg-white rounded-3 shadow-sm" id="dashboardTabs" role="tablist" style="max-width: 320px;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 border-0 px-3 py-2 fw-semibold" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><i class="bi bi-grid-1x2 me-1"></i>{{ __('ui.dashboard.overview') }}</button>
        </li>
        @if($canViewPayments)
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 border-0 px-3 py-2 fw-semibold text-secondary" id="payment-details-tab" data-bs-toggle="tab" data-bs-target="#payment-details" type="button" role="tab"><i class="bi bi-receipt me-1"></i>{{ __('ui.dashboard.payment_details') }}</button>
        </li>
        @endif
    </ul>
    @endif
</div>

@php
    $roleSlug = \App\Models\User::normalizeRoleSlug((string) auth()->user()->role);
@endphp
@if(in_array($roleSlug, ['accountant', 'vice_principal_afp'], true) && auth()->user()->canModule('finance_payments', 'update') && ($arrearsFollowUp ?? collect())->isNotEmpty())
<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-cash-coin me-2"></i>Arrears to follow up</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Student</th><th>Programme</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
                @foreach($arrearsFollowUp as $s)
                <tr>
                    <td><a href="{{ route('students.show', $s) }}">{{ $s->reg_no }} — {{ $s->full_name }}</a></td>
                    <td>{{ $s->programme->code ?? '' }}</td>
                    <td class="text-end fw-semibold text-danger">{{ number_format($s->balance) }} TZS</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($roleSlug === 'examination_officer' && auth()->user()->canModule('results', 'update') && ($resultsPendingEntry ?? 0) > 0)
<div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
    <span><i class="bi bi-exclamation-triangle me-2"></i><strong>{{ $resultsPendingEntry }}</strong> module(s) this academic year have no results entered yet.</span>
    <a href="{{ route('results.index') }}" class="btn btn-sm btn-warning">Enter results</a>
</div>
@endif
@endif

<style>
.nav-pills .nav-link { color: #64748b; }
.nav-pills .nav-link.active { background: var(--cohas-gradient); color: #fff; }
.payment-entry { padding: 1.25rem 0; }
.payment-entry-num { font-size: 1.5rem; font-weight: 700; color: #94a3b8; }
.payment-sep { text-align: center; color: #cbd5e1; letter-spacing: .2em; padding: .5rem 0; border-bottom: 1px dashed #e2e8f0; }
.text-paid { color: #16a34a; font-weight: 600; }
.text-balance { color: #dc2626; font-weight: 600; }
.text-expiry { color: #16a34a; }
</style>

@if(auth()->user()->isStudent())
    @if(auth()->user()->student && !auth()->user()->student->hasCompletedSemesterRegistration())
    <div class="alert alert-warning mb-4 border-0 shadow-sm">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Not registered for the semester.</strong>
            <a href="{{ route('my.registrations') }}" class="alert-link">Check status</a>.
    </div>
    @endif

    @if($studentDashboard ?? null)
        @include('dashboard.partials.student-srms')
    @endif
@else
<div class="tab-content" id="dashboardTabsContent">
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row g-3 mb-4 dashboard-stat-grid">
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-card__icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="dashboard-stat-card__label">{{ __('ui.dashboard.total_students') }}</div>
                    <div class="dashboard-stat-card__value">{{ number_format($studentCount ?? 0) }}</div>
                    <div class="small text-muted">{{ number_format($activeStudentCount ?? 0) }} {{ __('ui.dashboard.on_register') }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-card__icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div class="dashboard-stat-card__label">{{ __('ui.dashboard.programmes') }}</div>
                    <div class="dashboard-stat-card__value">{{ number_format($programmeCount ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-card__icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="dashboard-stat-card__label">{{ __('ui.dashboard.academic_year') }}</div>
                    <div class="dashboard-stat-card__value" style="font-size:1.15rem">{{ \App\Support\AcademicSession::label($academicYear ?? \App\Support\AcademicSession::defaultStartYear()) }}</div>
                    <span class="badge bg-success rounded-pill mt-1">{{ __('ui.dashboard.active') }}</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-card__icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="dashboard-stat-card__label">Graduated students</div>
                    <div class="dashboard-stat-card__value">{{ number_format($graduatedCount ?? 0) }}</div>
                    <form method="GET" class="mt-1">
                        <label for="graduation_year" class="visually-hidden">Graduation year</label>
                        <select id="graduation_year" name="graduation_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach(($graduationYearOptions ?? [now()->year]) as $year)
                                <option value="{{ $year }}" {{ (int) ($graduationYear ?? now()->year) === (int) $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            @if($canViewPayments)
            <div class="col-6 col-lg-3">
                <div class="dashboard-stat-card">
                    <div class="dashboard-stat-card__icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="dashboard-stat-card__label">{{ __('ui.dashboard.payments_today') }}</div>
                    <div class="dashboard-stat-card__value">{{ number_format($paymentsToday ?? 0) }}</div>
                    <div class="small text-muted">TZS</div>
                </div>
            </div>
            @endif
        </div>

        @include('dashboard.partials.admin-analytics')
        @if(isset($announcements) && $announcements->isNotEmpty())
        <div class="card card-landing mb-4">
            <div class="card-header-landing"><i class="bi bi-megaphone me-2"></i>{{ __('ui.dashboard.announcements') }}</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($announcements as $a)
                    <li class="mb-3 pb-3 border-bottom"><strong>{{ $a->title }}</strong>
                        @if($a->show_until)<span class="text-muted small"> – until {{ $a->show_until->format('d/m/Y') }}</span>@endif
                        @if($a->body)<p class="mb-0 mt-1 small text-muted">{{ \Illuminate\Support\Str::limit($a->body, 200) }}</p>@endif
                    </li>
                    @endforeach
                </ul>
                @if(!auth()->user()->isStudent())<a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary">Manage announcements</a>@endif
            </div>
        </div>
        @endif

        <div class="row g-3">
            @if($canViewRegistrations)
            <div class="col-lg-8">
                <div class="card card-modern">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-calendar-event me-2 text-primary"></i>{{ __('ui.dashboard.registration_activity') }}</h5>
                        <a href="{{ route('semester-registrations.index') }}" class="btn btn-sm btn-primary rounded-pill">{{ __('ui.dashboard.view_all') }}</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('ui.dashboard.semester') }}</th>
                                        <th>{{ __('ui.dashboard.pending') }}</th>
                                        <th>{{ __('ui.dashboard.approved') }}</th>
                                        <th class="text-end">{{ __('ui.dashboard.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($semestersForDashboard ?? [] as $sem)
                                    @php
                                        $counts = $registrationCounts[$sem->id] ?? collect();
                                        $pending = $counts->where('status', 'pending')->sum('cnt');
                                        $approved = $counts->where('status', 'approved')->sum('cnt');
                                    @endphp
                                    <tr>
                                        <td>{{ $sem->label }}</td>
                                        <td>{{ $pending }}</td>
                                        <td>{{ $approved }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('semester-registrations.index', ['semester_id' => $sem->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('ui.dashboard.manage') }}</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No semesters. Add semesters in the system.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($canCreateRegistrations)
                        <p class="text-muted small mb-0 mt-2"><a href="{{ route('registration-wizard.start') }}">Start step-by-step registration</a> · <a href="{{ route('semester-registrations.create') }}">Quick submit</a></p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            <div class="{{ $canViewRegistrations ? 'col-lg-4' : 'col-12' }}">
                <div class="card card-modern h-100">
                    <div class="card-header">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-lightning me-2 text-warning"></i>{{ __('ui.dashboard.quick_actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('students.create') }}" class="btn btn-outline-primary btn-modern text-start">
                                <i class="bi bi-person-plus me-2"></i>{{ __('ui.dashboard.register_student') }}
                            </a>
                            @endif
                            @if($canViewPayments)
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-primary btn-modern text-start">
                                <i class="bi bi-clock-history me-2"></i>{{ __('ui.dashboard.payment_history') }}
                            </a>
                            @endif
                            @if($canCreateProgrammes)
                            <a href="{{ route('programmes.create') }}" class="btn btn-outline-primary btn-modern text-start">
                                <i class="bi bi-journal-plus me-2"></i>{{ __('ui.dashboard.add_programme') }}
                            </a>
                            @endif
                            @if($canCreateFeeStructures)
                            <a href="{{ route('fee-structures.create') }}" class="btn btn-outline-primary btn-modern text-start">
                                <i class="bi bi-currency-exchange me-2"></i>{{ __('ui.dashboard.add_fee_schedule') }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canViewPayments)
    <div class="tab-pane fade" id="payment-details" role="tabpanel">
        <div class="card card-modern">
            <div class="card-header">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>{{ __('ui.dashboard.payment_details') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Control number</th><th>Student</th><th>Amount (TZS)</th><th>Method</th><th>Balance after</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments ?? [] as $p)
                            <tr>
                                <td>{{ $p->paid_at->format('d/m/Y H:i') }}</td>
                                <td><code>{{ $p->reference ?? $p->id }}</code></td>
                                <td>{{ $p->student?->full_name ?? '—' }} ({{ $p->student?->reg_no ?? '' }})</td>
                                <td>{{ number_format($p->amount, 0) }}</td>
                                <td>{{ ucfirst($p->payment_method ?? '—') }}</td>
                                <td>{{ number_format($p->student?->balance ?? 0, 0) }}</td>
                                <td><a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-primary">Receipt</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">{{ __('ui.dashboard.no_payments') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
@endsection

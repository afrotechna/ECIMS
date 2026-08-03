@extends('layouts.app')
@section('title', 'Exam timetable')
@section('content')
@if(session('error'))
<div class="alert alert-warning no-print">{{ session('error') }}</div>
@endif
@if(session('success'))
<div class="alert alert-success no-print">{{ session('success') }}</div>
@endif
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Exam timetable</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h1 class="page-title-landing mb-0"><i class="bi bi-calendar-event me-2 opacity-90"></i>Exam timetable</h1>
    <div class="d-flex flex-wrap gap-2 no-print align-items-center">
        <a href="{{ route('exam-slots.create', array_filter(['semester_id' => $semesterId, 'programme_id' => $programmeId])) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add slots</a>
        @if($semesterId && $programmeId && isset($allSlots) && $allSlots->isNotEmpty())
            <a href="{{ route('exam-slots.export-docx', ['semester_id' => $semesterId, 'programme_id' => $programmeId, 'assessment_type' => \App\Models\ExamSlot::ASSESSMENT_CAT1]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-word me-1"></i>CAT I</a>
            <a href="{{ route('exam-slots.export-docx', ['semester_id' => $semesterId, 'programme_id' => $programmeId, 'assessment_type' => \App\Models\ExamSlot::ASSESSMENT_CAT2]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-word me-1"></i>CAT II</a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print or save as PDF"><i class="bi bi-printer me-1"></i>Print</button>
        @endif
    </div>
</div>

<form method="GET" class="mb-3 no-print row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-0">Semester</label>
        <select name="semester_id" class="form-select form-select-sm" style="min-width: 220px">
            <option value="">All (list view)</option>
            @foreach($semesters as $s)
                <option value="{{ $s->id }}" {{ (string) $semesterId === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Programme</label>
        <select name="programme_id" class="form-select form-select-sm" style="min-width: 200px">
            <option value="">— All programmes —</option>
            @foreach($programmes as $p)
                <option value="{{ $p->id }}" {{ (string) ($programmeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Assessment</label>
        <select name="assessment_type" class="form-select form-select-sm" style="min-width: 180px">
            <option value="" {{ ($assessmentType ?? '') === '' ? 'selected' : '' }}>All (CAT I &amp; II)</option>
            <option value="cat1" {{ ($assessmentType ?? '') === 'cat1' ? 'selected' : '' }}>CAT I only</option>
            <option value="cat2" {{ ($assessmentType ?? '') === 'cat2' ? 'selected' : '' }}>CAT II only</option>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button></div>
</form>

@if($semesterId && ! $programmeId)
<div class="alert alert-info no-print small">
    Select a <strong>programme</strong> to view the timetable.
</div>
@endif

@if($semesterId && $programmeId && $selectedProgramme && ! empty($timetableSections))
<div class="card card-landing mb-4 exam-timetable-official">
    <div class="card-body p-4">
        <div class="text-center timetable-letterhead mb-4">
            <div class="fw-bold small text-uppercase mb-1">The United Republic of Tanzania</div>
            <div class="fw-bold text-uppercase mb-1">Ministry of Health</div>
            <div class="fw-bold text-uppercase mb-1">Musoma Clinical Officer Training Center</div>
            <div class="fw-bold text-uppercase mb-2">{{ strtoupper($selectedProgramme->name) }} (NTA Level 4, 5 &amp; 6)</div>
            <div class="fw-bold text-uppercase mb-1" style="letter-spacing: .02em;">{{ $mainTitleLine }}</div>
            <div class="fw-bold text-uppercase">{{ $timetableMonthYear }}</div>
        </div>

        @foreach($timetableSections as $section)
            <h2 class="h6 text-uppercase fw-bold mt-4 mb-2 exam-timetable-section-title">{{ $section['section_label'] }}</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 exam-timetable-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 16%">Day</th>
                            <th scope="col" style="width: 22%">Time</th>
                            <th scope="col" style="width: 14%">Module code</th>
                            <th scope="col">Module name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['days'] as $day)
                            @php $dayFirst = true; @endphp
                            @foreach($day['sessions'] as $session)
                                @foreach($session['slots'] as $idx => $slot)
                                    <tr>
                                        @if($dayFirst)
                                            <td class="fw-semibold text-uppercase align-top" rowspan="{{ $day['row_count'] }}">
                                                {{ $day['day_upper'] }}<br>
                                                <span class="fw-normal text-muted small">{{ $day['date_display'] }}</span>
                                            </td>
                                            @php $dayFirst = false; @endphp
                                        @endif
                                        @if($idx === 0)
                                            <td class="align-top text-nowrap" rowspan="{{ count($session['slots']) }}">{{ $session['time_label'] }}</td>
                                        @endif
                                        <td class="font-monospace text-uppercase">{{ $slot->course?->code }}</td>
                                        <td class="text-uppercase">
                                            {{ $slot->course?->name }}
                                            <div class="small text-muted mt-1">
                                                {{ $slot->formatDisplayLabel() }}
                                                @if($slot->notes)
                                                    <span>· {{ strtoupper($slot->notes) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @php
            $footerPrefix = match ($assessmentType ?? '') {
                'cat2' => 'Continuous Assessment II Time Table',
                'cat1' => 'Continuous Assessment I Time Table',
                default => 'Continuous Assessment Time Table',
            };
        @endphp
        <div class="exam-timetable-doc-footer pt-3 mt-4 text-center small text-uppercase text-muted border-top border-dark">
            {{ $footerPrefix }} {{ $timetableFooterMonthYear }}
        </div>
    </div>
</div>
@elseif($semesterId && $programmeId && $selectedProgramme)
<div class="alert alert-info no-print">No exam slots for this programme and filter yet. Add slots or widen the assessment filter.</div>
@endif

@php
    $canDeleteExamSlots = auth()->user()?->canModule('exams', 'delete') ?? false;
    $canUpdateExamSlots = auth()->user()?->canModule('exams', 'update') ?? false;
    $examSlotsOnPage = $slots->count();
    $examSlotTableCols = 6 + ($canDeleteExamSlots ? 1 : 0) + (($canDeleteExamSlots || $canUpdateExamSlots) ? 1 : 0);
    $bulkDelete = [
        'bulkModule' => 'exams',
        'bulkAction' => route('exam-slots.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteExamSlots',
        'bulkTableId' => 'examSlotsTable',
        'bulkItemCount' => $examSlotsOnPage,
        'bulkHidden' => array_filter([
            'semester_id' => $semesterId ?? null,
            'programme_id' => $programmeId ?? null,
            'assessment_type' => ($assessmentType ?? '') !== '' ? $assessmentType : null,
        ]),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>{{ $semesterId ? 'Filtered exam slots' : 'All exam slots' }}</span>
        <div class="d-flex flex-wrap align-items-center gap-2 no-print">
            @if($slots instanceof \Illuminate\Contracts\Pagination\Paginator && $slots->hasPages())
                <span class="small text-muted">Page {{ $slots->currentPage() }}</span>
            @endif
            @include('partials.bulk-delete.toolbar', $bulkDelete)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="examSlotsTable">
            <thead>
                <tr>
                    @include('partials.bulk-delete.th', $bulkDelete)
                    <th>Date</th>
                    <th>Time</th>
                    <th>Course</th>
                    <th>Format</th>
                    <th>CAT</th>
                    <th>Room</th>
                    @if($canDeleteExamSlots || $canUpdateExamSlots)
                        <th class="no-print text-end" scope="col" style="width: 1%"><span class="small text-muted text-uppercase" style="letter-spacing: .04em">Actions</span></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                <tr>
                    @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $slot->id]))
                    <td>{{ $slot->exam_date->format('d/m/Y') }}</td>
                    <td>
                        @if($slot->start_time)
                            @php
                                $ts = \Carbon\Carbon::parse($slot->start_time)->format('g:i A');
                                $te = $slot->end_time ? \Carbon\Carbon::parse($slot->end_time)->format('g:i A') : null;
                            @endphp
                            {{ $te ? $ts.' – '.$te : $ts }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="font-monospace">{{ $slot->course ? $slot->course->code : '' }}</span>
                        {{ $slot->course ? $slot->course->name : '' }}
                        @if($slot->course?->programme)
                            <div class="small text-muted">{{ $slot->course->programme->code }}</div>
                        @endif
                    </td>
                    <td class="small text-uppercase">{{ $slot->formatDisplayLabel() }}</td>
                    <td class="small">{{ \App\Models\ExamSlot::assessmentOptions()[$slot->assessment_type] ?? $slot->assessment_type }}</td>
                    <td>{{ $slot->room ?? '—' }}</td>
                    @if($canDeleteExamSlots || $canUpdateExamSlots)
                    <td class="no-print text-nowrap text-end">
                        <div class="d-inline-flex align-items-center gap-1 exam-slot-row-actions" role="group" aria-label="Slot actions">
                            @canModule('exams', 'update')
                                @include('partials.action-edit', ['href' => route('exam-slots.edit', $slot), 'title' => 'Edit slot', 'iconOnly' => true])
                            @endcanModule
                            @canModule('exams', 'delete')
                                <form method="POST" action="{{ route('exam-slots.destroy', $slot) }}" class="d-inline m-0">
                                    @csrf
                                    @method('DELETE')
                                    @if($semesterId)<input type="hidden" name="semester_id" value="{{ $semesterId }}">@endif
                                    @if($programmeId)<input type="hidden" name="programme_id" value="{{ $programmeId }}">@endif
                                    @if(($assessmentType ?? '') !== '')<input type="hidden" name="assessment_type" value="{{ $assessmentType }}">@endif
                                    @include('partials.action-delete', ['title' => 'Delete slot', 'swalTitle' => 'Remove this exam slot?', 'swalText' => 'Remove this exam slot ('.e($slot->course?->code ?? '').' on '.$slot->exam_date->format('d/m/Y').')? This cannot be undone.'])
                                </form>
                            @endcanModule
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $examSlotTableCols }}" class="text-center text-muted py-5">No exam slots.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($slots instanceof \Illuminate\Contracts\Pagination\Paginator && $slots->hasPages())
<div class="mt-3 no-print">{{ $slots->links() }}</div>
@endif

@include('partials.bulk-delete.scripts')

@push('styles')
<style>
    .exam-slot-icon-btn {
        width: 2.125rem;
        height: 2.125rem;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
    }
    .exam-slot-icon-btn i { font-size: 1rem; line-height: 1; }
    .exam-timetable-table th, .exam-timetable-table td { vertical-align: middle; font-size: 0.9rem; }
    .exam-timetable-table > tbody > tr > td { white-space: normal; }
    .timetable-letterhead { line-height: 1.35; }
    .exam-timetable-doc-footer { page-break-inside: avoid; }
    @media print {
        .no-print, .student-breadcrumb, .page-header-landing .no-print, form.mb-3, .sidebar, nav, .btn, .card-header-landing { display: none !important; }
        .exam-timetable-official { border: none !important; box-shadow: none !important; }
        .exam-timetable-official .card-body { padding: 0 !important; }
        body { background: #fff !important; }
        .main-content, .container-fluid { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .exam-timetable-doc-footer { border-top: 2px solid #000 !important; padding-top: 0.75rem !important; margin-top: 1.5rem !important; }
    }
</style>
@endpush
@endsection

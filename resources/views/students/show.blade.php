@extends('layouts.app')

@section('title', 'Student — '.$student->full_name)

@section('content')
<nav class="student-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <span aria-current="page">{{ $student->full_name }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="min-w-0">
        <h1 class="page-title-landing text-truncate"><i class="bi bi-person-badge me-2 opacity-90"></i>{{ $student->full_name }}</h1>
        <p class="page-subtitle-landing mb-0 text-break">
            @if($student->official_registry_no)
                <span class="badge bg-secondary">{{ $student->official_registry_no }}</span> ·
            @endif
            <span class="text-nowrap">{{ $student->registrationNumberDisplay() }}</span>
            · {{ $student->programme->code ?? '—' }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap flex-shrink-0">
        @if(auth()->user()->canAccessFinance())
        <a href="{{ route('students.ledger', $student) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-wallet2 me-1"></i> Ledger</a>
        @endif
        @canModule('finance_payments', 'view')
        <a href="{{ route('payments.index', ['student_id' => $student->id]) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-clock-history me-1"></i> Payment history</a>
        @endcanModule
        <a href="{{ route('results.transcript.show', $student) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-file-earmark-text me-1"></i> Transcript</a>
        @canModule('student_card_status', 'view')
        <a href="{{ route('students.id-card', $student) }}" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-person-vcard me-1"></i> ID card</a>
        @endcanModule
        <a href="{{ route('students.edit', $student) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-pencil me-1"></i> Edit</a>
        <a href="{{ route('students.index') }}" class="btn btn-outline-light btn-sm">Back to list</a>
    </div>
</div>

<div class="row g-4 align-items-start">
    <div class="col-lg-8">
        <div class="card card-landing mb-4">
            <div class="card-header-landing py-2"><i class="bi bi-info-circle me-2"></i>Student details</div>
            <div class="card-body">
                <dl class="row student-detail-dl mb-0 small gx-2">
                    <dt class="col-4 col-md-3 text-muted text-nowrap">Form IV Index number</dt>
                    <dd class="col-8 col-md-9 student-detail-dd"><code class="small bg-light px-2 py-1 rounded">{{ $student->registrationNumberDisplay() ?: '—' }}</code></dd>

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Full name</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->full_name }}</dd>

                    @if($student->gender)
                    <dt class="col-4 col-md-3 text-muted text-nowrap">Gender</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->gender === 'M' ? 'Male' : 'Female' }}</dd>
                    @endif

                    @if($student->class_group)
                    <dt class="col-4 col-md-3 text-muted text-nowrap">Class / group</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->class_group }}</dd>
                    @endif

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Programme</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->programme->name ?? '—' }} <span class="text-muted">({{ $student->programme->code ?? '—' }})</span></dd>

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Intake year</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->intake_year }}</dd>

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Status</dt>
                    <dd class="col-8 col-md-9 student-detail-dd"><span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ $student->status }}</span></dd>

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Email</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->email ?? '—' }}</dd>

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Phone</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->phone ?? '—' }}</dd>

                    @if($student->date_of_birth)
                    <dt class="col-4 col-md-3 text-muted text-nowrap">Date of birth</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ $student->date_of_birth->format('d/m/Y') }}</dd>
                    @endif

                    @if($student->nta_level)
                    <dt class="col-4 col-md-3 text-muted text-nowrap">NTA level</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">{{ \App\Models\Student::NTA_LEVELS[$student->nta_level] ?? 'Level '.$student->nta_level }}</dd>
                    @endif

                    <dt class="col-4 col-md-3 text-muted text-nowrap">Student type</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">
                        <span class="badge bg-{{ $student->student_type === 'transferred' ? 'info' : 'secondary' }}">{{ \App\Models\Student::STUDENT_TYPES[$student->student_type ?? 'regular'] ?? $student->student_type }}</span>
                    </dd>

                    @if($student->student_type === 'transferred' && ($student->transfer_date || $student->previous_institution))
                    <dt class="col-4 col-md-3 text-muted text-nowrap">Transfer</dt>
                    <dd class="col-8 col-md-9 student-detail-dd">
                        @if($student->transfer_date){{ $student->transfer_date->format('d/m/Y') }}@endif
                        @if($student->previous_institution) · {{ $student->previous_institution }}@endif
                        @if($student->previousProgramme) · {{ $student->previousProgramme->code }}@endif
                    </dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card card-landing mb-4">
            <div class="card-header-landing py-2"><i class="bi bi-journal-check me-2"></i>Semester enrolment</div>
            <div class="card-body p-0">
                @if($student->semesterRegistrations->isEmpty())
                    <p class="text-muted mb-0 p-4">No semester registrations yet.</p>
                @else
                    <div class="table-responsive table-responsive-students-landing">
                        <table class="table table-hover align-middle mb-0 table-students-landing">
                            <thead>
                                <tr>
                                    <th scope="col">Academic year</th>
                                    <th scope="col">Semester</th>
                                    <th scope="col">Starting date</th>
                                    <th scope="col">Ending date</th>
                                    <th scope="col" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->semesterRegistrations->sortByDesc(fn ($r) => $r->semester?->academic_year ?? 0) as $reg)
                                <tr>
                                    <td>{{ $reg->semester?->academicYearRange() ?? '—' }}</td>
                                    <td><strong>{{ $reg->semester?->periodName() ?? '—' }}</strong></td>
                                    <td>{{ $reg->semester?->start_date ? $reg->semester->start_date->format('d/m/Y') : '—' }}</td>
                                    <td>{{ $reg->semester?->end_date ? $reg->semester->end_date->format('d/m/Y') : '—' }}</td>
                                    <td class="text-center">
                                        @if($reg->status === 'approved')<span class="badge bg-success">Registered</span>
                                        @elseif($reg->status === 'rejected')<span class="badge bg-danger">Rejected</span>
                                        @else<span class="badge bg-warning text-dark">Pending</span>@endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if($student->moduleEnrollments->isNotEmpty() && ! auth()->user()->isStudent())
        <div class="card card-landing mb-4">
            <div class="card-header-landing py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-bookmark me-2"></i>Registered modules (current selections)</span>
                <a href="{{ route('clinical-logbook.student', $student) }}" class="btn btn-sm btn-outline-light">Clinical progress</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Semester</th><th>Code</th><th>Module</th><th>Repeat?</th></tr></thead>
                        <tbody>
                            @foreach($student->moduleEnrollments->sortByDesc('semester_id') as $me)
                            <tr>
                                <td class="small">{{ $me->semester?->label }}</td>
                                <td class="fw-semibold">{{ $me->course?->code }}</td>
                                <td>{{ $me->course?->name }}</td>
                                <td>@if($me->is_carry_repeat)<span class="badge bg-warning text-dark">Yes</span>@else—@endif</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(isset($student->studentDocuments))
        <div class="card card-landing">
            <div class="card-header-landing py-2"><i class="bi bi-file-earmark me-2"></i>Documents</div>
            <div class="card-body">
                @if(!auth()->user()->isStudent())
                <form action="{{ route('students.documents.store', $student) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end mb-4 pb-3 border-bottom">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small mb-1 text-muted" for="doc-type">Type</label>
                        <select name="type" id="doc-type" class="form-select form-select-sm" required>
                            @foreach(\App\Models\StudentDocument::TYPES as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1 text-muted" for="doc-file">File</label>
                        <input type="file" name="document" id="doc-file" class="form-control form-control-sm" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload</button>
                    </div>
                </form>
                @endif
                @if($student->studentDocuments->isEmpty())
                    <p class="text-muted mb-0">No documents uploaded.</p>
                @else
                    <div class="table-responsive table-responsive-students-landing">
                        <table class="table table-hover align-middle mb-0 table-students-landing">
                            <thead>
                                <tr>
                                    <th scope="col">Type</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Uploaded</th>
                                    <th scope="col" class="text-end actions-cell">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->studentDocuments as $doc)
                                <tr>
                                    <td>{{ \App\Models\StudentDocument::TYPES[$doc->type] ?? $doc->type }}</td>
                                    <td>{{ $doc->name ?? '—' }}</td>
                                    <td class="text-nowrap">{{ $doc->created_at->format('d/m/Y') }}</td>
                                    <td class="text-end actions-cell">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('student-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                                            @if(!auth()->user()->isStudent())
                                            <form action="{{ route('student-documents.destroy', $doc) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                @include('partials.action-delete', ['title' => 'Remove', 'swalTitle' => 'Remove this document?'])
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if(!auth()->user()->isStudent())
        <div class="card card-landing mb-3">
            <div class="card-header-landing py-2"><i class="bi bi-people me-2"></i>Parent / guardian portal</div>
            <div class="card-body small">
                @if($guardianUser ?? null)
                <p class="mb-2"><span class="badge bg-success">Active</span> {{ $guardianUser->email }}</p>
                <form action="{{ route('students.guardian-access.destroy', $student) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-sm btn-outline-danger" data-swal-confirm data-swal-title="Remove guardian login?">Remove access</button>
                </form>
                @else
                <form action="{{ route('students.guardian-access.store', $student) }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label mb-0">Guardian email</label>
                        <input type="email" name="guardian_email" class="form-control form-control-sm" required placeholder="parent@example.com">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Create guardian login</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
        @endif
        <div class="card card-landing student-glance-card border-0 shadow-sm sticky-top" style="top: 0.75rem;">
            <div class="card-header-landing py-2"><i class="bi bi-card-heading me-2"></i>At a glance</div>
            <div class="card-body py-3">
                <div class="glance-item">
                    <div class="glance-label">Registration</div>
                    <div class="glance-value font-monospace small">{{ $student->registrationNumberDisplay() ?: '—' }}</div>
                </div>
                <div class="glance-item">
                    <div class="glance-label">Programme</div>
                    <div class="glance-value">{{ $student->programme->code ?? '—' }}</div>
                </div>
                <div class="glance-item">
                    <div class="glance-label">Intake · NTA</div>
                    <div class="glance-value">
                        {{ $student->intake_year }}
                        @if($student->nta_level)
                            · <span class="badge bg-secondary">{{ $student->nta_level }}</span>
                        @endif
                    </div>
                </div>
                <div class="glance-item">
                    <div class="glance-label">Status</div>
                    <div><span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ $student->status }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

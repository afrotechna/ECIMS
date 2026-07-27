@extends('layouts.app')

@section('title', 'Modules by semester')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Modules</a>
    <span class="mx-2">/</span>
    <span>By semester</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-book-fill me-2 opacity-90"></i>Modules by semester</h1>
        <p class="page-subtitle-landing mb-0">Filter by semester and programme. For the full catalogue (programme · NTA · semester), use <a href="{{ route('courses.index') }}">Modules</a>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-book me-1"></i>Module catalogue</a>
        @canModule('courses', 'create')
        <a href="{{ route('courses.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Course</a>
        @endcanModule
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form action="{{ route('academics.index') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="semester_id" class="form-label">Semester</label>
                <select name="semester_id" id="semester_id" class="form-select">
                    <option value="">Select semester</option>
                    @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ ($semesterId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="programme_id" class="form-label">Programme</label>
                <select name="programme_id" id="programme_id" class="form-select">
                    <option value="">All programmes</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ ($programmeId ?? '') == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Show</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Courses / Modules (CA to SE)</span>
        @if($currentSemester)
            <span class="badge bg-light text-dark">{{ $currentSemester->label }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(!$semesterId)
            <p class="text-muted mb-0">Select a semester above to view courses and modules.</p>
        @elseif($courses->isEmpty())
            <p class="text-muted mb-0">
                No courses assigned to this semester yet.
                @canModule('courses', 'create')
                <a href="{{ route('courses.create') }}">Add a course</a> and assign it to this semester.
                @else
                Contact the ICT administrator to add modules.
                @endcanModule
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Course / Module</th>
                            <th>Programme</th>
                            <th>Year</th>
                            <th>CA %</th>
                            <th>SE %</th>
                            <th>Credits</th>
                            @canModule('courses', 'update')
                            <th class="text-end">Actions</th>
                            @endcanModule
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $i => $c)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $c->code }}</strong></td>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->programme->code ?? '—' }}</td>
                            <td>{{ $c->year_of_study }}</td>
                            <td>{{ $c->ca_weight }}</td>
                            <td>{{ $c->exam_weight }}</td>
                            <td>{{ $c->credits }}</td>
                            @canModule('courses', 'update')
                            <td class="text-end">
                                @include('partials.action-edit', ['href' => route('courses.edit', array_filter([
                                    'course' => $c,
                                    'return_semester_id' => $semesterId,
                                    'return_programme_id' => $programmeId ?: $c->programme_id,
                                ])), 'iconOnly' => true])
                            </td>
                            @endcanModule
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

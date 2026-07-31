@extends('layouts.app')
@section('title', 'Student ID & NHIF card status')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Student ID &amp; NHIF card status</span>
</nav>
<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-person-vcard me-2 opacity-90"></i>Student ID &amp; NHIF card status</h1>
    <p class="page-subtitle-landing mb-0">Track card production and notify active students automatically when their card is printed or ready.</p>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('student-card-status.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reg. no or name">
            </div>
            <div class="col-md-3">
                <label class="form-label">Programme</label>
                <select name="programme_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ (string) request('programme_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Reg. no</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Student ID card</th>
                    <th>NHIF card</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $byType = $student->cardStatuses->keyBy('document_type');
                @endphp
                <tr>
                    <td class="fw-medium">{{ $student->reg_no }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->programme->code ?? '—' }}</td>
                    @foreach(\App\Models\StudentCardStatus::DOCUMENT_TYPES as $type => $label)
                    @php $current = $byType->get($type); @endphp
                    <td>
                        <form method="POST" action="{{ route('student-card-status.update', $student) }}" class="d-flex align-items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="document_type" value="{{ $type }}">
                            <select name="status" class="form-select form-select-sm" data-no-search onchange="this.form.submit()" style="width:auto">
                                @foreach(\App\Models\StudentCardStatus::STATUSES as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" {{ ($current->status ?? 'pending') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">No active students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($students->hasPages())<div class="mt-3">{{ $students->links() }}</div>@endif
@endsection

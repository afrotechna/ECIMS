@extends('layouts.app')
@section('title', 'Payment instalments')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Payment instalments</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-calendar2-check me-2 opacity-90"></i>Payment instalments</h1>
        <p class="page-subtitle-landing mb-0">Plan fee instalments per student. Record payments on the ledger as usual; mark instalments paid here.</p>
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-0">Filter by student</label>
                <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All students</option>
                    @foreach($students as $s)
                    <option value="{{ $s->id }}" {{ (string) $studentId === (string) $s->id ? 'selected' : '' }}>{{ $s->reg_no }} — {{ $s->full_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($studentId)
            <div class="col-auto">
                <a href="{{ route('payment-instalments.index') }}" class="btn btn-sm btn-outline-secondary">Clear filter</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-plus-circle me-2"></i>Add instalment</div>
            <div class="card-body">
                <form action="{{ route('payment-instalments.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                            <option value="">Select student</option>
                            @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id', $studentId) == $s->id ? 'selected' : '' }}>{{ $s->reg_no }} — {{ $s->full_name }}</option>
                            @endforeach
                        </select>
                        @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="label" class="form-label">Label</label>
                        <input type="text" class="form-control" id="label" name="label" value="{{ old('label') }}" placeholder="e.g. Semester 1 — 50%">
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" min="1" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date') }}" required>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save instalment</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Instalment schedule</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Label</th>
                                <th>Due</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instalments as $inst)
                            <tr>
                                <td class="small">
                                    <strong>{{ $inst->student->reg_no }}</strong><br>
                                    <span class="text-muted">{{ $inst->student->full_name }}</span>
                                </td>
                                <td>{{ $inst->label ?? '—' }}</td>
                                <td class="small">{{ $inst->due_date->format('d/m/Y') }}</td>
                                <td>{{ number_format($inst->amount) }}</td>
                                <td>{{ number_format($inst->paid_amount) }}</td>
                                <td>
                                    @php $st = $inst->status; @endphp
                                    <span class="badge bg-{{ $st === 'paid' ? 'success' : ($st === 'overdue' ? 'danger' : ($st === 'partial' ? 'warning text-dark' : 'secondary')) }}">
                                        {{ \App\Models\PaymentInstalment::STATUSES[$st] ?? $st }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('students.ledger', $inst->student) }}" class="btn btn-sm btn-outline-primary" title="Ledger">Ledger</a>
                                    <button type="button" class="btn btn-sm btn-cohas-edit" data-bs-toggle="modal" data-bs-target="#editInstalment{{ $inst->id }}" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                    <form action="{{ route('payment-instalments.destroy', $inst) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this instalment?');">
                                        @csrf
                                        @method('DELETE')
                                        @include('partials.action-delete', ['submit' => true])
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No instalments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($instalments->hasPages())
            <div class="card-footer bg-light border-0 py-2">{{ $instalments->links() }}</div>
            @endif
        </div>
    </div>
</div>

@foreach($instalments as $inst)
<div class="modal fade" id="editInstalment{{ $inst->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('payment-instalments.update', $inst) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit instalment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" value="{{ $inst->label }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (TZS)</label>
                        <input type="number" class="form-control" name="amount" value="{{ $inst->amount }}" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due date</label>
                        <input type="date" class="form-control" name="due_date" value="{{ $inst->due_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paid amount (TZS)</label>
                        <input type="number" class="form-control" name="paid_amount" value="{{ $inst->paid_amount }}" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

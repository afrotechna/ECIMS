@extends('layouts.app')
@section('title', 'Edit Creditor')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('creditors.index') }}">Accountancy · Creditors</a>
    <span class="mx-2">/</span>
    <span>{{ $creditor->payee_name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>{{ $creditor->payee_name }}</h1>
    <p class="page-subtitle-landing mb-0">
        Due {{ number_format($creditor->amount_due) }} &middot;
        Paid {{ number_format($creditor->paidTotal()) }} &middot;
        Balance <strong>{{ number_format($creditor->balance()) }}</strong>
    </p>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Creditor details</div>
            <div class="card-body">
                <form action="{{ route('creditors.update', $creditor) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                                @foreach(\App\Models\Creditor::CATEGORIES as $k => $label)
                                <option value="{{ $k }}" {{ old('category', $creditor->category) === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="verification_status" class="form-label">Verification status</label>
                            <select class="form-select" id="verification_status" name="verification_status">
                                @foreach(\App\Models\Creditor::VERIFICATION_STATUSES as $k => $label)
                                <option value="{{ $k }}" {{ $creditor->verification_status === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="payee_name" class="form-label">Payee <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('payee_name') is-invalid @enderror" id="payee_name" name="payee_name" value="{{ old('payee_name', $creditor->payee_name) }}" required>
                            @error('payee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">—</option>
                                @foreach($departments as $dep)
                                <option value="{{ $dep->id }}" {{ (int) old('department_id', $creditor->department_id) === $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $creditor->description) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="amount_due" class="form-label">Amount due <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount_due') is-invalid @enderror" id="amount_due" name="amount_due" value="{{ old('amount_due', $creditor->amount_due) }}" required>
                            @error('amount_due')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="date_incurred" class="form-label">Date incurred <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date_incurred') is-invalid @enderror" id="date_incurred" name="date_incurred" value="{{ old('date_incurred', $creditor->date_incurred?->toDateString()) }}" required>
                            @error('date_incurred')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date', $creditor->due_date?->toDateString()) }}">
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Creditor</button>
                        <a href="{{ route('creditors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-cash-coin me-2"></i>Record payment</div>
            <div class="card-body">
                @if($creditor->balance() <= 0.005)
                    <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>Fully paid.</p>
                @else
                    <form action="{{ route('creditors.pay', $creditor) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label for="cash_account_id" class="form-label">Paid from <span class="text-danger">*</span></label>
                            <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                                <option value="">Choose…</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $creditor->balance() }}" class="form-control" id="amount" name="amount" value="{{ $creditor->balance() }}" required>
                            <div class="form-text">Outstanding balance: {{ number_format($creditor->balance()) }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="txn_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="txn_date" name="txn_date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100" data-swal-confirm data-swal-title="Record this payment?" data-swal-icon="question"><i class="bi bi-cash-coin me-1"></i> Record Payment</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

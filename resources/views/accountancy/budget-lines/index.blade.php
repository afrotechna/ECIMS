@extends('layouts.app')
@section('title', 'Department Budgets')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accountancy</span>
    <span class="mx-2">/</span>
    <span>Department Budgets</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-pie-chart me-2 opacity-90"></i>Department Budgets</h1>
        <p class="page-subtitle-landing mb-0">Annual budget per department, with actual spend to date.</p>
    </div>
    @canModule('accountancy', 'create')
    <a href="{{ route('budget-lines.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Budget Line</a>
    @endcanModule
</div>

<form method="GET" class="card card-landing mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Financial year</label>
            <input type="text" name="financial_year" class="form-control form-control-sm" style="width:130px" value="{{ $financialYear }}" placeholder="2026/2027">
        </div>
        <div>
            <label class="form-label small mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm" style="width:200px">
                <option value="">All departments</option>
                @foreach($departments as $dep)
                <option value="{{ $dep->id }}" {{ (int) $departmentId === $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="{{ route('budget-lines.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>FY</th>
                        <th>Item</th>
                        <th class="text-end">Budget</th>
                        <th class="text-end">Spent</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                    @php($a = $line->actuals())
                    <tr>
                        <td>{{ $line->department->name ?? '—' }}</td>
                        <td>{{ $line->financial_year }}</td>
                        <td>{{ $line->item_description }}</td>
                        <td class="text-end">{{ number_format($a['budget']) }}</td>
                        <td class="text-end">{{ number_format($a['spent']) }}</td>
                        <td class="text-end {{ $a['balance'] < 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($a['balance']) }}</td>
                        <td class="text-end">
                            @canModule('accountancy', 'update')
                            @include('partials.action-edit', ['href' => route('budget-lines.edit', $line), 'iconOnly' => true])
                            @endcanModule
                            @canModule('accountancy', 'delete')
                            <form action="{{ route('budget-lines.destroy', $line) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete budget line?', 'swalText' => 'Only possible if it has no transactions.'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No budget lines match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($lines->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $lines->links() }}</div>
    @endif
</div>
@endsection

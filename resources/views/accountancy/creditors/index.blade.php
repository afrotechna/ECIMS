@extends('layouts.app')
@section('title', 'Creditors')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accountancy</span>
    <span class="mx-2">/</span>
    <span>Creditors</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-file-earmark-ruled me-2 opacity-90"></i>Creditors</h1>
        <p class="page-subtitle-landing mb-0">Money owed to staff, suppliers &amp; examination bodies.</p>
    </div>
    @canModule('accountancy', 'create')
    <a href="{{ route('creditors.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Creditor</a>
    @endcanModule
</div>

<form method="GET" class="card card-landing mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Category</label>
            <select name="category" class="form-select form-select-sm" style="width:200px">
                <option value="">All categories</option>
                @foreach(\App\Models\Creditor::CATEGORIES as $k => $label)
                <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small mb-1">Status</label>
            <select name="verification_status" class="form-select form-select-sm" style="width:200px">
                <option value="">All statuses</option>
                @foreach(\App\Models\Creditor::VERIFICATION_STATUSES as $k => $label)
                <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="{{ route('creditors.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Payee</th>
                        <th>Category</th>
                        <th>Due date</th>
                        <th>Ageing</th>
                        <th class="text-end">Due</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditors as $c)
                    <tr>
                        <td><a href="{{ route('creditors.edit', $c) }}"><strong>{{ $c->payee_name }}</strong></a></td>
                        <td>{{ $c->categoryLabel() }}</td>
                        <td>{{ $c->due_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @php($bucket = $c->ageingBucket())
                            <span class="badge {{ $bucket === 'Not yet due' ? 'bg-secondary' : 'bg-warning text-dark' }}">{{ $bucket }}</span>
                        </td>
                        <td class="text-end">{{ number_format($c->amount_due) }}</td>
                        <td class="text-end {{ $c->balance() > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format($c->balance()) }}</td>
                        <td><span class="badge bg-info text-dark">{{ $c->statusLabel() }}</span></td>
                        <td class="text-end">
                            @canModule('accountancy', 'update')
                            @include('partials.action-edit', ['href' => route('creditors.edit', $c), 'iconOnly' => true])
                            @endcanModule
                            @canModule('accountancy', 'delete')
                            <form action="{{ route('creditors.destroy', $c) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete creditor?', 'swalText' => 'Only possible if no payments are recorded.'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No creditors match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($creditors->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $creditors->links() }}</div>
    @endif
</div>
@endsection

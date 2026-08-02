@extends('layouts.app')
@section('title', 'Ledger - ' . $student->full_name)
@section('content')
<div class="mb-4">
    <a href="{{ route('students.show', $student) }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> {{ $student->full_name }}</a>
</div>
<h2 class="h4 mb-4">Ledger / Statement</h2>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><strong>{{ $student->full_name }}</strong></div>
            @php $feeBal = $student->balanceSummary(); @endphp
            <div class="col-md-6 text-md-end"><strong>{{ $feeBal['label'] }}:</strong> <span class="text-{{ $feeBal['class'] }}">{{ number_format($feeBal['amount'], 0) }} TZS</span></div>
        </div>
    </div>
</div>
<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance after</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->created_at->format('d/m/Y H:i') }}</td>
                        <td>@if($e->type === 'credit')<span class="badge bg-success">Credit</span>@else<span class="badge bg-secondary">Debit</span>@endif</td>
                        <td>{{ $e->description ?? '-' }}</td>
                        <td class="text-end">{{ $e->type === 'credit' ? '-' : '' }}{{ number_format($e->amount, 0) }} TZS</td>
                        <td class="text-end">{{ $e->balance_after !== null ? number_format($e->balance_after, 0) . ' TZS' : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No ledger entries yet. Payments will appear here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-white">{{ $entries->links() }}</div>
    @endif
</div>
@if(auth()->user()->canAccessFinance())
<div class="mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="card-title">Add charge (bill)</h6>
            <form action="{{ route('students.ledger.charge', $student) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <label class="form-label small mb-0">Amount (TZS)</label>
                    <input type="number" name="amount" class="form-control form-control-sm" min="1" step="1" required>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Description</label>
                    <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Tuition 2024/25" maxlength="255">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Add charge</button>
                </div>
            </form>
        </div>
    </div>
    <a href="{{ route('payments.index', ['student_id' => $student->id]) }}" class="btn btn-outline-primary btn-sm mt-2">Payment history for this student</a>
</div>
@endif
@endsection

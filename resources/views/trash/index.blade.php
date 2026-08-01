@extends('layouts.app')
@section('title', 'Trash')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Trash</span>
</nav>
<div class="page-header-landing mb-3">
    <h1 class="page-title-landing"><i class="bi bi-trash3 me-2 opacity-90"></i>Trash</h1>
    <p class="page-subtitle-landing mb-0">Deleted records are kept here, not erased. Restore them or delete permanently.</p>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('trash.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Record type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    @foreach($types as $key => $config)
                    <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $config['label'] }} ({{ $counts[$key] }})</option>
                    @endforeach
                </select>
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
                    <th>Record</th>
                    <th>Deleted at</th>
                    <th>Deleted by</th>
                    <th class="text-center" style="width:11rem">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td>{{ $display($record) }}</td>
                    <td class="small">{{ $record->deleted_at?->format('d M Y H:i') }}</td>
                    <td class="small">{{ $record->deletedBy->name ?? '—' }}</td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('trash.restore', ['type' => $type, 'id' => $record->id]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm" title="Restore" aria-label="Restore">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('trash.force-delete', ['type' => $type, 'id' => $record->id]) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            @include('partials.action-delete', ['title' => 'Delete permanently', 'swalTitle' => 'Delete permanently?', 'swalText' => 'This cannot be undone — the record (and any attached file) will be gone for good.'])
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-5">Nothing in Trash for {{ strtolower($types[$type]['label']) }}.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif
@endsection

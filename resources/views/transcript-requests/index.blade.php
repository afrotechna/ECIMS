@extends('layouts.app')
@section('title', 'Transcript requests')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Transcript requests</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-file-earmark-text me-2 opacity-90"></i>Transcript requests</h1>
    <p class="page-subtitle-landing mb-0">Students request official transcripts; mark ready or rejected when processed.</p>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Purpose</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td>{{ $req->student->reg_no }} — {{ $req->student->full_name }}</td>
                        <td class="small">{{ $req->purpose ?? '—' }}</td>
                        <td class="small">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge bg-{{ $req->status === 'ready' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'warning text-dark') }}">{{ \App\Models\TranscriptRequest::STATUSES[$req->status] ?? $req->status }}</span></td>
                        <td class="text-end">
                            @if($req->status === 'pending')
                            <form action="{{ route('transcript-requests.update', $req) }}" method="POST" class="d-inline-flex gap-1 align-items-start flex-wrap justify-content-end">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="ready">
                                <input type="text" name="staff_notes" class="form-control form-control-sm" placeholder="Notes" style="max-width:140px">
                                <button type="submit" class="btn btn-sm btn-success">Ready</button>
                            </form>
                            <form action="{{ route('transcript-requests.update', $req) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                            @else
                            <span class="small text-muted">{{ $req->processor?->name ?? '—' }} {{ $req->processed_at?->format('d/m/Y') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No transcript requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
    <div class="card-footer">{{ $requests->links() }}</div>
    @endif
</div>
@endsection

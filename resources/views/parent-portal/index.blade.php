@extends('layouts.app')
@section('title', 'Parent portal')
@section('content')
@php $student = $student; @endphp
<nav class="student-breadcrumb">
    <a href="{{ route('parent.portal') }}">Parent portal</a>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-people me-2 opacity-90"></i>Parent / guardian portal</h1>
    <p class="page-subtitle-landing mb-0">Viewing: <strong>{{ $student->full_name }}</strong> — {{ $student->programme->code ?? '' }}</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-wallet2 me-2"></i>Finance</div>
            <div class="card-body">
                @if($recentPayments->isNotEmpty())
                <ul class="list-unstyled small mb-0">
                    @foreach($recentPayments as $p)
                    <li class="mb-2 pb-2 border-bottom">
                        {{ number_format($p->amount) }} TZS — {{ $p->paid_at?->format('d/m/Y') }}
                        <a href="{{ route('payments.receipt', $p) }}" class="ms-1">Receipt</a>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">No recent payments recorded.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-journal-check me-2"></i>Results (published)</div>
            <div class="card-body">
                @if($recentResults->isNotEmpty())
                <ul class="list-unstyled small mb-0">
                    @foreach($recentResults as $r)
                    <li class="mb-1">{{ $r->course->code ?? '' }}: {{ $r->grade ?? '—' }} ({{ $r->total_mark ?? '—' }})</li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">No published results yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-megaphone me-2"></i>Announcements</div>
            <div class="card-body small">
                @forelse($announcements as $a)
                <p class="mb-2"><strong>{{ $a->title }}</strong><br>{{ Str::limit($a->body, 120) }}</p>
                @empty
                <p class="text-muted mb-0">No announcements.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card card-landing">
            <div class="card-header-landing d-flex justify-content-between align-items-center">
                <span><i class="bi bi-folder2-open me-2"></i>College documents</span>
                <a href="{{ route('college-documents.index') }}" class="btn btn-sm btn-outline-primary">Browse folders</a>
            </div>
            <div class="card-body">
                @if(config('college.integrations.moodle_url'))
                <p class="mb-2"><a href="{{ config('college.integrations.moodle_url') }}" target="_blank" rel="noopener">Open Moodle (learning platform)</a></p>
                @endif
                @if(config('college.integrations.library_url'))
                <p class="mb-0"><a href="{{ config('college.integrations.library_url') }}" target="_blank" rel="noopener">Open library system</a></p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

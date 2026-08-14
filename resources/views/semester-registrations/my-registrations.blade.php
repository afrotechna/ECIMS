@extends('layouts.app')
@section('title', 'My Registrations')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My Registrations</span>
</nav>
@php
    $latestApproved = $registrations->firstWhere('status', 'approved');
@endphp
<div class="page-header-landing">
    <h1 class="page-title-landing">My Registrations</h1>
    <p class="page-subtitle-landing mb-0">{{ $student->full_name }}</p>
    @if($latestApproved)
    <p class="page-subtitle-landing mb-0 mt-1"><strong>You are registered for {{ $latestApproved->semester->periodName() }} of academic year {{ $latestApproved->semester->academicYearRange() }}.</strong></p>
    @endif
</div>


<div class="card card-landing">
    <div class="card-header-landing">My registrations</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Registration number</th><th>Academic year</th><th>Registration date</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($registrations as $r)
                <tr>
                    <td>{{ $student->nactvet_reg_no }}</td>
                    <td>{{ $r->semester->academicYearRange() }}</td>
                    <td>{{ $r->registered_at ? $r->registered_at->format('d/m/Y') : '—' }}</td>
                    <td>
                        @if($r->status === 'approved')
                        <span class="badge bg-success">Registered</span>
                        @elseif($r->status === 'rejected')
                        <span class="badge bg-danger">Rejected</span>
                        @else
                        <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-5">No semester registration yet. Visit the accounts office after payment.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection

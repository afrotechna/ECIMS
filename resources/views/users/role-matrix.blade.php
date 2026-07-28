@extends('layouts.app')
@section('title', 'Role permissions matrix')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.role-permissions') }}">Role permissions</a>
    <span class="mx-2">/</span>
    <span>Role defaults</span>
</nav>

<div class="page-header-landing mb-4">
    <h1 class="page-title-landing"><i class="bi bi-shield-lock me-2 opacity-90"></i>Role permissions by module</h1>
    <p class="page-subtitle-landing mb-0">What each position may <strong>view</strong>, <strong>create</strong>, <strong>update</strong>, or <strong>delete</strong> by default.</p>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: .8rem;">
                <thead class="table-light">
                    <tr>
                        <th class="sticky-start bg-light" style="min-width: 200px;">Module</th>
                        @foreach($roles as $role)
                        <th class="text-center" style="min-width: 120px;">{{ $role['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $moduleKey => $moduleLabel)
                    <tr>
                        <th class="sticky-start bg-white">{{ $moduleLabel }}</th>
                        @foreach($roles as $role)
                        @php
                            $grants = $matrix[$role['key']] ?? [];
                            if (isset($grants['*'])) {
                                $allowed = $grants['*'];
                            } else {
                                $allowed = $grants[$moduleKey] ?? [];
                            }
                        @endphp
                        <td class="text-center">
                            @if(in_array('*', $allowed, true) || count(array_intersect($actions, $allowed)) === count($actions))
                                <span class="badge bg-success">Full</span>
                            @elseif($allowed === [])
                                <span class="text-muted">—</span>
                            @else
                                @foreach($actions as $act)
                                    @if(in_array('*', $allowed, true) || in_array($act, $allowed, true))
                                    <span class="badge bg-secondary me-1">{{ strtoupper(substr($act, 0, 1)) }}</span>
                                    @endif
                                @endforeach
                                <div class="small text-muted mt-1">
                                    @foreach($actions as $act)
                                        @if(in_array('*', $allowed, true) || in_array($act, $allowed, true))
                                            {{ $act }}@if(!$loop->last), @endif
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<p class="small text-muted mt-3 mb-0">
    <strong>Legend:</strong> V = view, C = create, U = update, D = delete.
    <strong>Academic portfolio (ARC):</strong> Vice Principal — Academic, Research &amp; Consultancy; Admission; HOD CMT; HOD MLT; Examination Officer; tutors; clinical instructors.
    <strong>Finance &amp; planning portfolio (AFP):</strong> Vice Principal — Administrative, Financial &amp; Planning; Accountant; Procurement Officer; Secretary; Accommodation Matron.
    Students use the portal only (not listed here).
</p>
@endsection

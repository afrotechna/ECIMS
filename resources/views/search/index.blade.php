@extends('layouts.app')
@section('title', 'Search')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Search</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-search me-2 opacity-90"></i>Search</h1>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('search.index') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Reg. no. or name</label>
                <input type="text" class="form-control" name="q" value="{{ $q }}" autofocus>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button></div>
        </form>
    </div>
</div>

@if($q !== '')
<div class="card card-landing">
    <div class="card-header-landing">Students @if($students->isNotEmpty())({{ $students->count() }})@endif</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Reg. no.</th><th>Name</th><th>Programme</th><th></th></tr></thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td>{{ $s->reg_no }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $s->programme->code ?? '' }}</td>
                    <td>@include('partials.action-view', ['href' => route('students.show', $s), 'title' => 'Open'])</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-5">No students matched "{{ $q }}".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

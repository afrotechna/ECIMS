@extends('layouts.app')
@section('title', 'Graduation clearances')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Graduation clearances</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-clipboard-check me-2 opacity-90"></i>Graduation clearances</h1>
    <p class="page-subtitle-landing mb-0">Mark library, finance, accommodation and academic clearance per student.</p>
</div>
<div class="card card-landing mb-3">
    <div class="card-header-landing">Add clearance</div>
    <div class="card-body">
        <form action="{{ route('graduation-clearances.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label for="student_id" class="form-label">Student</label>
                <select class="form-select" id="student_id" name="student_id" required>
                    <option value="">Select</option>
                    @foreach(\App\Models\Student::where('status', 'active')->orderBy('reg_no')->get() as $s)
                        <option value="{{ $s->id }}">{{ $s->reg_no }} — {{ $s->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Library</label><select class="form-select" name="library_cleared"><option value="no">No</option><option value="yes">Yes</option></select></div>
            <div class="col-md-2"><label class="form-label">Finance</label><select class="form-select" name="finance_cleared"><option value="no">No</option><option value="yes">Yes</option></select></div>
            <div class="col-md-2"><label class="form-label">Accommodation</label><select class="form-select" name="accommodation_cleared"><option value="no">No</option><option value="yes">Yes</option></select></div>
            <div class="col-md-2"><label class="form-label">Academic</label><select class="form-select" name="academic_cleared"><option value="no">No</option><option value="yes">Yes</option></select></div>
            <div class="col-12"><label class="form-label">Notes</label><input type="text" class="form-control" name="notes"></div>
            <div class="col-12"><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
<div class="card card-landing">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Student</th><th>Library</th><th>Finance</th><th>Accommodation</th><th>Academic</th><th></th></tr></thead>
            <tbody>
                @forelse($clearances as $c)
                <tr>
                    <td>{{ $c->student->reg_no ?? '' }} — {{ $c->student->full_name ?? '' }}</td>
                    <td>{{ $c->library_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                    <td>{{ $c->finance_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                    <td>{{ $c->accommodation_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                    <td>{{ $c->academic_cleared === 'yes' ? 'Yes' : 'No' }}</td>
                    <td>@include('partials.action-edit', ['href' => route('graduation-clearances.edit', $c), 'iconOnly' => true])</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No clearances.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($clearances->hasPages())<div class="mt-3">{{ $clearances->links() }}</div>@endif
@endsection

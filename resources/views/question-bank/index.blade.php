@extends('layouts.app')
@section('title', 'Question Bank')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Question Bank</span>
</nav>

<div class="page-header-landing d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title-landing">Question Bank</h1>
    </div>
</div>

<div class="card card-landing mb-4">
    <div class="card-header-landing">Create New Bank</div>
    <div class="card-body">
        <form method="POST" action="{{ route('question-bank.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Bank name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course</label>
                <select name="course_id" class="form-select">
                    <option value="">-- None --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Create Bank</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing">Available Banks</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Materials</th>
                    <th>Questions</th>
                    <th>Exams</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($banks as $bank)
                    <tr>
                        <td>{{ $bank->name }}</td>
                        <td>{{ $bank->course?->code ?? '-' }}</td>
                        <td>{{ $bank->materials_count }}</td>
                        <td>{{ $bank->questions_count }}</td>
                        <td>{{ $bank->exams_count }}</td>
                        <td>
                            <a href="{{ route('question-bank.show', $bank) }}" class="btn btn-sm btn-outline-primary">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No question banks yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $banks->links('pagination::bootstrap-5') }}</div>
@endsection

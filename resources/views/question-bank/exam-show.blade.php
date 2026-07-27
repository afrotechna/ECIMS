@extends('layouts.app')
@section('title', $examPaper->title)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('question-bank.index') }}">Question Bank</a>
    <span class="mx-2">/</span>
    <a href="{{ route('question-bank.show', $questionBank) }}">{{ $questionBank->name }}</a>
    <span class="mx-2">/</span>
    <span>{{ $examPaper->title }}</span>
</nav>

<div class="page-header-landing d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title-landing">{{ $examPaper->title }}</h1>
        <p class="page-subtitle-landing mb-0">
            {{ strtoupper($examPaper->assessment_type ?? 'exam') }} | {{ $examPaper->exam_type ?: '-' }} |
            Duration: {{ $examPaper->duration_minutes ?? '-' }} mins | Total marks: {{ $examPaper->total_marks }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('question-bank.exams.export-docx', [$questionBank, $examPaper]) }}" class="btn btn-light">Question Paper (.docx)</a>
        <a href="{{ route('question-bank.exams.export-docx', [$questionBank, $examPaper]) }}?with_answers=1" class="btn btn-outline-light">Answer Guide (.docx)</a>
        <form method="POST" action="{{ route('question-bank.exams.destroy', [$questionBank, $examPaper]) }}" class="d-inline-flex align-items-center gap-2 ms-1" onsubmit="return confirm('Delete this assessment? Questions remain in the bank unless you remove them separately.');">
            @csrf
            @method('DELETE')
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="confirm_delete" value="1" id="confirm_del_exam" required>
                <label class="form-check-label text-white-50 small" for="confirm_del_exam">Confirm delete</label>
            </div>
            <button type="submit" class="btn btn-outline-warning btn-sm">Delete assessment</button>
        </form>
    </div>
</div>

<p class="small text-muted mb-3">If a downloaded .docx will not open, ensure PHP has the <code>zip</code> extension enabled in Laragon (PHP → Extensions → php_zip), or rely on the built-in fallback used by the app. Open in Microsoft Word or LibreOffice.</p>

<div class="card card-landing">
    <div class="card-header-landing">Questions</div>
    <div class="card-body">
        @foreach($examPaper->items as $item)
            <div class="mb-3 border-bottom pb-2">
                <span class="badge bg-secondary">Section {{ $item->section_label ?: '-' }}</span>
                <strong>{{ $item->question_order }}.</strong> {{ $item->question->stem }}
                <span class="badge bg-primary ms-2">{{ $item->marks }} marks</span>
            </div>
        @endforeach
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit document')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('institution-documents.index') }}">Institution documents</a>
    @if($folderLabel)
    <span class="mx-2">/</span>
    <a href="{{ route('institution-documents.index', ['category' => $folderKey]) }}">{{ $folderLabel }}</a>
    @endif
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit document</h1>
</div>

<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('institution-documents.update', $document) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $document->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Folder <span class="text-danger">*</span></label>
                    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                        @foreach(\App\Models\InstitutionDocument::CATEGORIES as $val => $label)
                        <option value="{{ $val }}" {{ old('category', $document->category) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if(\App\Models\InstitutionDocument::isStudentCategory(old('category', $document->category)))
                <div class="col-md-4">
                    <label for="programme_id" class="form-label">Programme</label>
                    <select class="form-select @error('programme_id') is-invalid @enderror" id="programme_id" name="programme_id">
                        <option value="">All programmes</option>
                        @foreach($programmes as $programme)
                        <option value="{{ $programme->id }}" {{ (string) old('programme_id', $document->programme_id) === (string) $programme->id ? 'selected' : '' }}>
                            {{ $programme->code }} — {{ $programme->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">e.g. CMT practicum guide — only Clinical Medicine students will see it.</div>
                </div>
                @endif
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $document->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="document" class="form-label">Replace file</label>
                    <input type="file" class="form-control @error('document') is-invalid @enderror" id="document" name="document">
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Leave empty to keep the current file.</div>
                    @if($document->original_name)
                    <p class="small text-muted mb-0 mt-2"><i class="bi bi-paperclip"></i> Current: {{ $document->original_name }} ({{ number_format($document->size / 1024, 0) }} KB)</p>
                    @endif
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    @if(\App\Models\InstitutionDocument::isStudentCategory(old('category', $document->category)))
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_public" value="1" id="is_public" {{ old('is_public', $document->is_public) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_public">Allow students to download</label>
                    </div>
                    @else
                    <p class="small text-muted mb-0">Administrative folders are not shown to students.</p>
                    @endif
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('institution-documents.index', ['category' => $document->category]) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

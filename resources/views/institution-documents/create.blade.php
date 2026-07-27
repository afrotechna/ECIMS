@extends('layouts.app')
@section('title', 'Upload institution document')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('institution-documents.index') }}">Institution documents</a>
    @if(!empty($folderLabel))
    <span class="mx-2">/</span>
    <a href="{{ route('institution-documents.index', ['category' => $folderKey]) }}">{{ $folderLabel }}</a>
  @endif
    <span class="mx-2">/</span>
    <span>Upload</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Upload document</h1>
    <p class="page-subtitle-landing mb-0">
        @if(!empty($folderLabel))
        Saving to folder: <strong>{{ $folderLabel }}</strong>. PDF, Word, Excel, or images up to 20 MB.
        @else
        Choose a folder and upload PDF, Word, Excel, or images up to 20 MB.
        @endif
    </p>
</div>

<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('institution-documents.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Folder <span class="text-danger">*</span></label>
                    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required {{ !empty($folderKey) ? 'disabled' : '' }}>
                        @foreach(\App\Models\InstitutionDocument::CATEGORIES as $val => $label)
                        <option value="{{ $val }}" {{ old('category', $folderKey) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(!empty($folderKey))
                    <input type="hidden" name="category" value="{{ $folderKey }}">
                    @endif
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @php $uploadCategory = old('category', $folderKey ?? ''); @endphp
                @if($uploadCategory === '' || \App\Models\InstitutionDocument::isStudentCategory($uploadCategory))
                <div class="col-md-4">
                    <label for="programme_id" class="form-label">Programme</label>
                    <select class="form-select @error('programme_id') is-invalid @enderror" id="programme_id" name="programme_id">
                        <option value="">All programmes</option>
                        @foreach($programmes as $programme)
                        <option value="{{ $programme->id }}" {{ (string) old('programme_id') === (string) $programme->id ? 'selected' : '' }}>
                            {{ $programme->code }} — {{ $programme->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Choose CMT, MLT, etc. so only that programme’s students see this file. Leave as all programmes for college-wide documents.</div>
                </div>
                @endif
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="document" class="form-label">File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control @error('document') is-invalid @enderror" id="document" name="document" required>
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    @if($uploadCategory === '' || \App\Models\InstitutionDocument::isStudentCategory($uploadCategory))
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_public" value="1" id="is_public" {{ old('is_public') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_public">Allow students to download</label>
                    </div>
                    @else
                    <p class="small text-muted mb-0">Administrative folders are not shown to students.</p>
                    @endif
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Upload</button>
                <a href="{{ !empty($folderKey) ? route('institution-documents.index', ['category' => $folderKey]) : route('institution-documents.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

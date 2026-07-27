@extends('layouts.app')

@section('title', 'Edit Programme')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('programmes.index') }}">Programmes</a>
    <span class="mx-2">/</span>
    <span>Edit {{ $programme->code }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit Programme</h1>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil me-2"></i>Programme</div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form action="{{ route('programmes.update', $programme) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="code" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('code') is-invalid @enderror" name="code" id="code" required>
                        @foreach($catalogue as $row)
                            <option
                                value="{{ $row['code'] }}"
                                {{ (string) old('code', $programme->code) === (string) $row['code'] ? 'selected' : '' }}
                            >
                                {{ $row['name'] }} ({{ $row['code'] }})
                                @if(!empty($row['legacy']))
                                    — on file
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                    <select class="form-select @error('level') is-invalid @enderror" name="level" id="level" required>
                        @foreach($levelOptions as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('level', $programme->level) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="duration_years" class="form-label">Duration <span class="text-danger">*</span></label>
                    <select class="form-select @error('duration_years') is-invalid @enderror" name="duration_years" id="duration_years" required>
                        @foreach($durationOptions as $years => $durLabel)
                            <option value="{{ $years }}" {{ (string) old('duration_years', $programme->duration_years) === (string) $years ? 'selected' : '' }}>{{ $durLabel }}</option>
                        @endforeach
                    </select>
                    @error('duration_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label d-block">Active</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $programme->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Programme</button>
                <a href="{{ route('programmes.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div id="nta-level-documents" class="card card-landing mt-4">
    <div class="card-header-landing"><i class="bi bi-folder2-open me-2"></i>NTA level supporting documents</div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            For each NTA year (Level 4, 5, and 6), upload <strong>assessment plan</strong>, <strong>practicum guide</strong>, and <strong>curriculum</strong> (PDF or Word, up to 15 MB each). Replacing a file keeps the same slot.
        </p>
        <div class="accordion" id="ntaDocsAccordion">
            @foreach([4, 5, 6] as $ntaLevel)
                @php
                    $levelLabel = \App\Models\Student::NTA_LEVELS[$ntaLevel] ?? 'NTA Level '.$ntaLevel;
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ntaHeading{{ $ntaLevel }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ntaCollapse{{ $ntaLevel }}" aria-expanded="false" aria-controls="ntaCollapse{{ $ntaLevel }}">
                            <i class="bi bi-mortarboard me-2"></i>{{ $levelLabel }}
                        </button>
                    </h2>
                    <div id="ntaCollapse{{ $ntaLevel }}" class="accordion-collapse collapse" aria-labelledby="ntaHeading{{ $ntaLevel }}" data-bs-parent="#ntaDocsAccordion">
                        <div class="accordion-body">
                            @foreach(\App\Models\ProgrammeNtaLevelDocument::typeLabels() as $typeKey => $typeLabel)
                                @php
                                    $current = $docsByLevelAndType->get($ntaLevel.'|'.$typeKey);
                                @endphp
                                <div class="border rounded p-3 mb-3 bg-light bg-opacity-50">
                                    <div class="fw-semibold mb-2">{{ $typeLabel }}</div>
                                    @if($current)
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small">
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($current->file_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-arrow-down me-1"></i>{{ \Illuminate\Support\Str::limit($current->original_name ?: 'Download', 48) }}
                                            </a>
                                            @if($current->uploader)
                                                <span class="text-muted">Uploaded by {{ $current->uploader->name }}</span>
                                            @endif
                                            <form method="POST" action="{{ route('programmes.nta-level-documents.destroy', [$programme, $current->id]) }}" class="d-inline ms-auto" onsubmit="return confirm('Remove this file?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="small text-muted mb-2">No file uploaded yet.</p>
                                    @endif
                                    <form action="{{ route('programmes.nta-level-documents.store', $programme) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                                        @csrf
                                        <input type="hidden" name="nta_level" value="{{ $ntaLevel }}">
                                        <input type="hidden" name="document_type" value="{{ $typeKey }}">
                                        <div class="col-md-8">
                                            <label class="form-label small mb-0">{{ $current ? 'Replace with' : 'Choose file' }} (PDF / DOC / DOCX)</label>
                                            <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,application/pdf" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-md-4">
                                                <i class="bi bi-cloud-upload me-1"></i>{{ $current ? 'Replace' : 'Upload' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

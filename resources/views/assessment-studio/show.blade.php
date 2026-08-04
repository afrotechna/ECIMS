@extends('layouts.app')
@section('title', $questionBank->name)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('assessment-studio.index') }}">Assessment Studio</a>
    <span class="mx-2">/</span>
    <span>{{ $questionBank->name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing">{{ $questionBank->name }}</h1>
    <p class="page-subtitle-landing mb-0">{{ $questionBank->description ?: 'No description' }}</p>
</div>

@php
    $matCount = $questionBank->materials->count();
    $hasQuestions = $questions->total() > 0;
    $allSectionsDone = collect($sectionProgress)->every(fn ($m) => $m['done']);
    $examCount = $exams->count();
    $overallPct = (int) round(
        ($matCount > 0 ? 25 : 0)
        + ($hasQuestions ? 25 : 0)
        + ($allSectionsDone ? 25 : 0)
        + ($examCount > 0 ? 25 : 0)
    );
    if ($matCount === 0) {
        $openStep = 1;
    } elseif (! $hasQuestions) {
        $openStep = 2;
    } else {
        $openStep = 3;
    }
@endphp

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card card-landing mb-0 h-100">
            <div class="card-header-landing py-2">Progress</div>
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted">Overall</span>
                    <span class="fw-semibold">{{ $overallPct }}%</span>
                </div>
                <div class="progress mb-3" style="height: 10px" role="progressbar" aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-primary" style="width: {{ $overallPct }}%"></div>
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge {{ $matCount > 0 ? 'bg-success' : 'bg-secondary' }}">1 Upload</span>
                    <span class="badge {{ $hasQuestions ? 'bg-success' : ($matCount > 0 ? 'bg-primary' : 'bg-secondary') }}">2 Questions</span>
                    <span class="badge {{ $examCount > 0 ? 'bg-success' : ($hasQuestions ? 'bg-primary' : 'bg-secondary') }}">3 Assessment</span>
                    <span class="badge {{ $examCount > 0 ? 'bg-success' : 'bg-secondary' }}">4 Export</span>
                </div>
                <div class="small text-muted mt-2 mb-0">Materials {{ $matCount }} &middot; Questions {{ $questions->total() }} &middot; Assessments {{ $examCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-landing mb-0 h-100 border-primary">
            <div class="card-header-landing py-2 bg-primary text-white">Next step</div>
            <div class="card-body py-3">
                @if($matCount === 0)
                    <p class="small mb-0">Open <strong>Step 1</strong> below and upload at least one source (or paste text).</p>
                @elseif(! $hasQuestions)
                    <p class="small mb-0">Open <strong>Step 2</strong> and generate or manually add your first questions.</p>
                @elseif($examCount === 0)
                    <p class="small mb-0">Open <strong>Step 3</strong>, build an exam, quiz, or assignment, then export.</p>
                @else
                    <p class="small mb-0">You have questions and assessments ready. Export from <strong>Step 3</strong> or create another paper.</p>
                @endif
                <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse{{ $openStep }}" aria-expanded="true">Focus step {{ $openStep }}</button>
            </div>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="questionBankAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="qbHeading1">
            <button class="accordion-button {{ $openStep === 1 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse1" aria-expanded="{{ $openStep === 1 ? 'true' : 'false' }}" aria-controls="qbCollapse1" data-bs-parent="#questionBankAccordion">
                Step 1 — Upload material <span class="badge bg-light text-dark ms-2">{{ $matCount }} source(s)</span>
            </button>
        </h2>
        <div id="qbCollapse1" class="accordion-collapse collapse {{ $openStep === 1 ? 'show' : '' }}" aria-labelledby="qbHeading1" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body">
                <form method="POST" action="{{ route('assessment-studio.materials.store', $questionBank) }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="material_type" class="form-select form-select-sm" required>
                            <option value="notes">Notes</option>
                            <option value="assessment_plan">Assessment Plan</option>
                            <option value="curriculum">Curriculum</option>
                            <option value="material">Material</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Files (multi)</label>
                        <input type="file" name="material_files[]" class="form-control form-control-sm" multiple accept=".txt,.pdf,.doc,.docx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Single file</label>
                        <input type="file" name="material_file" class="form-control form-control-sm" accept=".txt,.pdf,.doc,.docx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Or paste text</label>
                        <textarea name="text_content" rows="3" class="form-control form-control-sm" placeholder="Plain text for generation"></textarea>
                    </div>
                    <div class="col-12"><button class="btn btn-primary btn-sm">Upload</button></div>
                </form>
                <p id="qbMaterialFilesPreview" class="small text-muted mb-0 mt-2 d-none" aria-live="polite"></p>

                @if($questionBank->materials->isNotEmpty())
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <span class="small text-uppercase text-muted fw-semibold">Uploaded sources ({{ $questionBank->materials->count() }})</span>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col" class="text-nowrap">Type</th>
                                        <th scope="col" class="text-nowrap">Source</th>
                                        <th scope="col" class="text-nowrap">Text for generation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questionBank->materials as $material)
                                        <tr>
                                            <td class="small">{{ $material->title }}</td>
                                            <td class="small text-nowrap">{{ str_replace('_', ' ', $material->material_type) }}</td>
                                            <td class="small">
                                                @if($material->file_path)
                                                    <a href="{{ asset('storage/'.$material->file_path) }}" target="_blank" rel="noopener" class="text-break">Download file</a>
                                                @else
                                                    <span class="text-muted">Pasted text</span>
                                                @endif
                                            </td>
                                            <td class="small">
                                                @if(filled($material->text_content))
                                                    <span class="badge bg-success">Ready</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Empty</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mb-0 mt-2">Each row is one material. Use the dropdowns below to pick which source to generate from.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="qbHeading2">
            <button class="accordion-button {{ $openStep === 2 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse2" aria-expanded="{{ $openStep === 2 ? 'true' : 'false' }}" aria-controls="qbCollapse2" data-bs-parent="#questionBankAccordion">
                Step 2 — Questions <span class="badge bg-light text-dark ms-2">{{ $questions->total() }} in bank</span>
            </button>
        </h2>
        <div id="qbCollapse2" class="accordion-collapse collapse {{ $openStep === 2 ? 'show' : '' }}" aria-labelledby="qbHeading2" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body p-0">
        <div class="card card-landing border-0 shadow-none rounded-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="small text-uppercase text-muted fw-semibold">Generate from material, or add by hand</div>
                    <a href="{{ route('assessment-studio.questions.create', $questionBank) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add question manually</a>
                </div>
                <div class="row g-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Generate</div>
                        <form method="POST" action="{{ route('assessment-studio.generate', $questionBank) }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Material source</label>
                                <select name="question_material_id" class="form-select form-select-sm" required>
                                    <option value="">-- Select material --</option>
                                    @foreach($questionBank->materials as $material)
                                        <option value="{{ $material->id }}">{{ $material->title }} ({{ $material->material_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Question type</label>
                                <select name="type" class="form-select form-select-sm" required>
                                    @foreach($typeLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Difficulty</label>
                                <select name="difficulty" class="form-select form-select-sm" required>
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Count</label>
                                <input type="number" name="count" class="form-control form-control-sm" value="5" min="1" max="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marks each</label>
                                <input type="number" step="0.5" name="marks" class="form-control form-control-sm" value="1">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="regenerate" value="1" id="regenerate_main">
                                    <label class="form-check-label" for="regenerate_main"><strong>Regenerate</strong></label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                                <button type="submit" class="btn btn-success btn-sm w-100">Generate &amp; Save</button>
                            </div>
                        </form>
                        <p class="small text-muted mt-2 mb-0">Generated questions are a draft built from your material &mdash; review and edit them (or add your own from scratch above) before using them on an assessment.</p>
                        <div class="mt-3 pt-2 border-top">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">All sections (A&ndash;E, for a full exam)</div>
                            <form method="POST" action="{{ route('assessment-studio.generate-all', $questionBank) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label mb-1 small">Material</label>
                                    <select name="question_material_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select --</option>
                                        @foreach($questionBank->materials as $material)
                                            <option value="{{ $material->id }}">{{ $material->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small">Difficulty</label>
                                    <select name="difficulty" class="form-select form-select-sm" required>
                                        <option value="easy">Easy</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small">Marks</label>
                                    <input type="number" step="0.5" name="marks" class="form-control form-control-sm" value="1">
                                </div>
                                <div class="col-12">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="regenerate" value="1" id="regen_all">
                                        <label class="form-check-label small" for="regen_all">Regen unused AI first</label>
                                    </div>
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-primary btn-sm">Generate all missing (fixed A&ndash;E counts)</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Section status (official exam structure)</div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <colgroup>
                                    <col style="width: 18%">
                                    <col style="width: 18%">
                                    <col style="width: 10%">
                                    <col style="width: 10%">
                                    <col style="width: 44%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Sec</th>
                                        <th class="text-nowrap">Progress</th>
                                        <th class="text-nowrap">Lck</th>
                                        <th class="text-nowrap">Rst</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sectionProgress as $section => $meta)
                                        <tr>
                                            <td class="text-nowrap">
                                                <strong>{{ $section }}</strong>
                                                <span class="text-muted ms-1">{{ $meta['label'] }}</span>
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="small">{{ $meta['existing'] }}/{{ $meta['required'] }}</span>
                                                @if($meta['remaining'] > 0) <span class="text-muted small">({{ $meta['remaining'] }} left)</span> @endif
                                            </td>
                                            <td class="text-center">
                                                @if($meta['locked'] > 0)
                                                    <span class="badge bg-secondary">{{ $meta['locked'] }}</span>
                                                @else
                                                    <span class="text-muted small">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($meta['resettable'] > 0)
                                                    <span class="badge bg-warning text-dark">{{ $meta['resettable'] }}</span>
                                                @else
                                                    <span class="text-muted small">0</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 justify-content-end text-nowrap">
                                                    @if($meta['can_add'])
                                                        <form method="POST" action="{{ route('assessment-studio.generate', $questionBank) }}" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="question_material_id" value="{{ $questionBank->materials->first()?->id }}">
                                                            <input type="hidden" name="type" value="{{ $meta['type'] }}">
                                                            <input type="hidden" name="difficulty" value="medium">
                                                            <input type="hidden" name="count" value="{{ min($meta['remaining'], 20) }}">
                                                            <input type="hidden" name="marks" value="1">
                                                            <button type="submit" class="btn btn-outline-primary btn-sm px-2" @if($questionBank->materials->isEmpty()) disabled @endif>Add</button>
                                                        </form>
                                                    @endif
                                                    @if($meta['can_regenerate'])
                                                        <form method="POST" action="{{ route('assessment-studio.generate', $questionBank) }}" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="regenerate" value="1">
                                                            <input type="hidden" name="question_material_id" value="{{ $questionBank->materials->first()?->id }}">
                                                            <input type="hidden" name="type" value="{{ $meta['type'] }}">
                                                            <input type="hidden" name="difficulty" value="medium">
                                                            <input type="hidden" name="count" value="{{ min($meta['required'], 20) }}">
                                                            <input type="hidden" name="marks" value="1">
                                                            <button type="button" class="btn btn-warning btn-sm px-2" @if($questionBank->materials->isEmpty()) disabled @endif data-swal-confirm data-swal-title="Regenerate section {{ $section }}?">Regen</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('assessment-studio.reset-section', $questionBank) }}" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="type" value="{{ $meta['type'] }}">
                                                            <input type="hidden" name="confirm_reset" value="1">
                                                            <button type="submit" class="btn btn-outline-warning btn-sm px-2">Reset</button>
                                                        </form>
                                                    @endif
                                                    @if($meta['is_blocked'])
                                                        <a href="#assessments" class="btn btn-sm btn-outline-secondary px-2" title="Assessments"><i class="bi bi-journal-text"></i><span class="ms-1">Asm</span></a>
                                                    @endif
                                                    @if(! $meta['can_add'] && ! $meta['can_regenerate'] && ! $meta['is_blocked'])
                                                        <button type="button" class="btn btn-success btn-sm px-2" disabled>Done</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2 mb-0">
                            <strong>Tip:</strong> Locked = on an assessment. Delete or change that assessment, then regen. This A&ndash;E table only matters if you're building the fixed-format Exam &mdash; quizzes and assignments don't need it.
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">All questions in this bank ({{ $questions->total() }})</div>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Stem</th>
                                    <th class="text-nowrap">Marks</th>
                                    <th class="text-nowrap">Source</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($questions as $question)
                                    <tr>
                                        <td class="small text-nowrap">{{ $typeLabels[$question->type] ?? $question->type }}</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit($question->stem, 90) }}</td>
                                        <td class="small text-nowrap">{{ $question->marks }}</td>
                                        <td class="small text-nowrap">
                                            @if($question->is_ai_generated)
                                                <span class="badge bg-info text-dark">Generated</span>
                                            @else
                                                <span class="badge bg-light text-dark border">Manual</span>
                                            @endif
                                            @if($question->exam_items_count > 0)
                                                <span class="badge bg-secondary" title="Used on a saved assessment">Locked</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('assessment-studio.questions.edit', [$questionBank, $question]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i></a>
                                            <form method="POST" action="{{ route('assessment-studio.questions.destroy', [$questionBank, $question]) }}" class="d-inline" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Delete this question?', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-cohas-delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3-fill"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No questions yet. Generate above or <a href="{{ route('assessment-studio.questions.create', $questionBank) }}">add one manually</a>.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">{{ $questions->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="qbHeading3">
            <button class="accordion-button {{ $openStep === 3 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse3" aria-expanded="{{ $openStep === 3 ? 'true' : 'false' }}" aria-controls="qbCollapse3" data-bs-parent="#questionBankAccordion">
                Step 3 — Build assessment &amp; export <span class="badge bg-light text-dark ms-2">{{ $examCount }} saved</span>
            </button>
        </h2>
        <div id="qbCollapse3" class="accordion-collapse collapse {{ $openStep === 3 ? 'show' : '' }}" aria-labelledby="qbHeading3" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body">
                <div class="row g-4">
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">What are you building?</label>
            <select id="assessmentKindPicker" class="form-select" style="max-width: 320px">
                <option value="exam">Exam &mdash; fixed A&ndash;E NACTVET format</option>
                <option value="flexible">Quiz / Assignment &mdash; flexible mix</option>
            </select>
        </div>

        <div class="card card-landing mb-3" id="examBuildPanel">
            <div class="card-header-landing">Build Exam (Sections A-E)</div>
            <div class="card-body py-3">
                <p class="small text-muted mb-3">
                    Uses the fixed official structure. Export DOCX from the list on the right once created.
                </p>
                @if(! $allSectionsDone)
                <div class="alert alert-warning small py-2">
                    Complete Step 2 first: each section needs the required question count (A=20, B=4, C=2, D=6, E=2).
                </div>
                @endif
                <form method="POST" action="{{ route('assessment-studio.exams.store', $questionBank) }}" id="qbCreateAssessmentForm">
                    @csrf
                    <input type="hidden" name="assessment_type" value="exam">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><input name="title" class="form-control" placeholder="Title" required></div>
                        <div class="col-md-6"><input name="exam_type" class="form-control" placeholder="CA I / End of Semester"></div>
                        <div class="col-md-3"><input name="module_code" class="form-control" placeholder="Module code"></div>
                        <div class="col-md-5"><input name="module_name" class="form-control" placeholder="Module name"></div>
                        <div class="col-md-4"><input name="duration_minutes" type="number" class="form-control" placeholder="Duration (mins)"></div>
                        <div class="col-md-6"><input name="examination_number" class="form-control" placeholder="Examination number"></div>
                        <div class="col-md-6"><input name="nactvet_reg_number" class="form-control" placeholder="NACTVET Reg Number"></div>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <p class="mb-2 text-muted small">Sections are fixed as A=20 MCQ, B=4 MTF, C=2 Matching, D=6 Short Answer, E=2 Essay. Enter marks allocation only.</p>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Section A Marks</label><input type="number" min="0" value="20" name="marks_a" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Section B Marks</label><input type="number" min="0" value="10" name="marks_b" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Section C Marks</label><input type="number" min="0" value="10" name="marks_c" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Section D Marks</label><input type="number" min="0" value="30" name="marks_d" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Section E Marks</label><input type="number" min="0" value="30" name="marks_e" class="form-control" required></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-1" @if(! $allSectionsDone) disabled title="Complete Step 2 first" @endif>Create Exam</button>
                </form>
            </div>
        </div>

        <div class="card card-landing mb-3 d-none" id="flexibleBuildPanel">
            <div class="card-header-landing">Build Quiz / Assignment</div>
            <div class="card-body py-3">
                <p class="small text-muted mb-3">Pick how many questions of each type you want and the marks per question &mdash; no fixed section layout.</p>
                <form method="POST" action="{{ route('assessment-studio.assessments.store-flexible', $questionBank) }}" id="qbCreateFlexibleForm">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><input name="title" class="form-control" placeholder="Title" required></div>
                        <div class="col-md-6">
                            <select name="assessment_type" class="form-select" required>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input name="exam_type" class="form-control" placeholder="e.g. Weekly quiz"></div>
                        <div class="col-md-4"><input name="module_code" class="form-control" placeholder="Module code"></div>
                        <div class="col-md-4"><input name="duration_minutes" type="number" class="form-control" placeholder="Duration (mins)"></div>
                        <div class="col-12"><input name="module_name" class="form-control" placeholder="Module name"></div>
                    </div>
                    <div class="border rounded p-3 mb-3">
                        <p class="mb-2 text-muted small">Add one row per question type you want to include.</p>
                        <div id="flexibleItemsList">
                            <div class="row g-2 align-items-center mb-2 flexible-item-row">
                                <div class="col-md-5">
                                    <select name="items[0][type]" class="form-select form-select-sm">
                                        @foreach($typeLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="items[0][count]" class="form-control form-control-sm" placeholder="Count" min="1" value="5">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.5" name="items[0][marks_each]" class="form-control form-control-sm" placeholder="Marks each" value="1">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm flexible-item-remove"><i class="bi bi-dash-lg"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="flexibleItemAdd" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add another type</button>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-1">Create Assessment</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        @php
            $qbBulkDelete = [
                'bulkModule' => 'question_bank',
                'bulkAction' => route('assessment-studio.exams.bulk-destroy', $questionBank),
                'bulkFormId' => 'bulkDeleteQbExams',
                'bulkScopeId' => 'qbExamsList',
                'bulkItemCount' => $examCount,
                'bulkConfirm' => 'Delete :count selected assessment(s)? Bank items stay; you can build new papers later.',
                'bulkButtonLabel' => 'Delete selected',
            ];
        @endphp
        <div class="card card-landing" id="assessments">
            <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Recent Exam / Quiz / Assignment</span>
                <div class="d-flex flex-wrap align-items-center gap-2 no-print">
                    @if($examCount > 0 && (auth()->user()?->canModule('question_bank', 'delete') ?? false))
                        <input type="checkbox" class="form-check-input bulk-delete-select-all" data-bulk-scope="qbExamsList" aria-label="Select all assessments">
                    @endif
                    @include('partials.bulk-delete.toolbar', $qbBulkDelete)
                </div>
            </div>
            <div class="card-body" id="qbExamsList" data-bulk-delete-scope>
                @forelse($exams as $exam)
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                        <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                            @include('partials.bulk-delete.checkbox-inline', array_merge($qbBulkDelete, ['bulkRowId' => $exam->id]))
                            <div class="min-w-0">
                            <strong>{{ $exam->title }}</strong> <span class="badge bg-secondary">{{ strtoupper($exam->assessment_type ?? 'exam') }}</span><br>
                            <small class="text-muted">Total Marks: {{ $exam->total_marks }} | {{ $exam->exam_type ?: '-' }}</small>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('assessment-studio.exams.show', [$questionBank, $exam]) }}">Open</a>
                            <a class="btn btn-sm btn-outline-success" href="{{ route('assessment-studio.exams.export-docx', [$questionBank, $exam]) }}">Export QP</a>
                            <a class="btn btn-sm btn-outline-success" href="{{ route('assessment-studio.exams.export-docx', [$questionBank, $exam]) }}?with_answers=1">Export Ans</a>
                            <form method="POST" action="{{ route('assessment-studio.exams.destroy', [$questionBank, $exam]) }}" class="d-inline-flex align-items-center gap-2" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Delete this exam/assignment/quiz?', text:'Bank items stay; you can build a new version and export again.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
                                @csrf
                                @method('DELETE')
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="confirm_delete" value="1" id="confirm_del_{{ $exam->id }}" required>
                                    <label class="form-check-label small" for="confirm_del_{{ $exam->id }}">Confirm</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-cohas-delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3-fill"></i><span class="ms-1">Delete</span></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No exams created yet.</p>
                @endforelse
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(($purgeableAiCount ?? 0) > 0)
<div class="card card-landing border-danger mt-4">
    <div class="card-header-landing bg-danger text-white">Reset generation (optional)</div>
    <div class="card-body">
        <p class="mb-2">You have <strong>{{ $purgeableAiCount }}</strong> generated question(s) that are <strong>not</strong> used in any saved assessment. Remove them to free section slots and generate new questions from your notes.</p>
        <p class="small text-muted mb-3">Questions already linked to an exam are kept until you delete that assessment (or remove items manually). Delete an old assessment above if you only want a new paper but the same pool is fine.</p>
        <form method="POST" action="{{ route('assessment-studio.purge-unused-ai', $questionBank) }}" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Remove all unused generated questions?', text:'This removes all unused generated questions from this bank. This cannot be undone.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
            @csrf
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm_purge" value="1" id="confirm_purge_ai" required>
                <label class="form-check-label" for="confirm_purge_ai">I understand these questions will be permanently deleted</label>
            </div>
            <button type="submit" class="btn btn-danger">Remove unused generated questions</button>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    var multi = document.querySelector('input[name="material_files[]"]');
    var single = document.querySelector('input[name="material_file"]');
    var out = document.getElementById('qbMaterialFilesPreview');
    if (out) {
        function namesFromInput(input) {
            if (!input || !input.files || !input.files.length) return [];
            return Array.prototype.map.call(input.files, function (f) { return f.name; });
        }
        function render() {
            var fromMulti = namesFromInput(multi);
            var fromSingle = namesFromInput(single);
            var list = fromMulti.length ? fromMulti : fromSingle;
            if (!list.length) {
                out.classList.add('d-none');
                out.textContent = '';
                return;
            }
            out.classList.remove('d-none');
            out.textContent = 'Selected file' + (list.length > 1 ? 's' : '') + ' (' + list.length + '): ' + list.join(', ');
        }
        if (multi) multi.addEventListener('change', render);
        if (single) single.addEventListener('change', render);
    }

    var kindPicker = document.getElementById('assessmentKindPicker');
    var examPanel = document.getElementById('examBuildPanel');
    var flexPanel = document.getElementById('flexibleBuildPanel');
    if (kindPicker && examPanel && flexPanel) {
        kindPicker.addEventListener('change', function () {
            var isExam = kindPicker.value === 'exam';
            examPanel.classList.toggle('d-none', !isExam);
            flexPanel.classList.toggle('d-none', isExam);
        });
    }

    var itemsList = document.getElementById('flexibleItemsList');
    var itemsAdd = document.getElementById('flexibleItemAdd');
    if (itemsList && itemsAdd) {
        var itemIndex = 1;
        itemsAdd.addEventListener('click', function () {
            var i = itemIndex++;
            var typeOptions = Array.prototype.map.call(itemsList.querySelector('select').options, function (o) {
                return '<option value="' + o.value + '">' + o.textContent + '</option>';
            }).join('');
            itemsList.insertAdjacentHTML('beforeend',
                '<div class="row g-2 align-items-center mb-2 flexible-item-row">' +
                '<div class="col-md-5"><select name="items[' + i + '][type]" class="form-select form-select-sm">' + typeOptions + '</select></div>' +
                '<div class="col-md-3"><input type="number" name="items[' + i + '][count]" class="form-control form-control-sm" placeholder="Count" min="1" value="5"></div>' +
                '<div class="col-md-3"><input type="number" step="0.5" name="items[' + i + '][marks_each]" class="form-control form-control-sm" placeholder="Marks each" value="1"></div>' +
                '<div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm flexible-item-remove"><i class="bi bi-dash-lg"></i></button></div>' +
                '</div>');
        });
        itemsList.addEventListener('click', function (e) {
            var btn = e.target.closest('.flexible-item-remove');
            if (!btn) return;
            var row = btn.closest('.flexible-item-row');
            if (row && itemsList.querySelectorAll('.flexible-item-row').length > 1) {
                row.remove();
            }
        });
    }
})();
</script>
@endpush
@include('partials.bulk-delete.scripts')
@endsection

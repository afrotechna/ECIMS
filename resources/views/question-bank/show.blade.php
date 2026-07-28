@extends('layouts.app')
@section('title', $questionBank->name)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('question-bank.index') }}">Question Bank</a>
    <span class="mx-2">/</span>
    <span>{{ $questionBank->name }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing">{{ $questionBank->name }}</h1>
    <p class="page-subtitle-landing mb-0">{{ $questionBank->description ?: 'No description' }}</p>
</div>

@php
    $matCount = $questionBank->materials->count();
    $allSectionsDone = collect($sectionProgress)->every(fn ($m) => $m['done']);
    $examCount = $exams->count();
    $sectionFillRatio = collect($sectionProgress)->sum(function ($m) {
        $req = max(1, (int) $m['required']);

        return min(1, (int) $m['existing'] / $req);
    }) / 5;
    $overallPct = (int) round(($matCount > 0 ? 25 : 0) + ($sectionFillRatio * 50) + ($examCount > 0 ? 25 : 0));
    if ($matCount === 0) {
        $openStep = 1;
    } elseif (! $allSectionsDone) {
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
                    <span class="badge {{ $allSectionsDone ? 'bg-success' : ($matCount > 0 ? 'bg-primary' : 'bg-secondary') }}">2 Generate Aâ€“E</span>
                    <span class="badge {{ $examCount > 0 ? 'bg-success' : ($allSectionsDone ? 'bg-primary' : 'bg-secondary') }}">3 Assessment</span>
                    <span class="badge {{ $examCount > 0 ? 'bg-success' : 'bg-secondary' }}">4 Export</span>
                </div>
                <div class="small text-muted mt-2 mb-0">Materials {{ $matCount }} Â· Questions {{ $questions->total() }} Â· Assessments {{ $examCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-landing mb-0 h-100 border-primary">
            <div class="card-header-landing py-2 bg-primary text-white">Next step</div>
            <div class="card-body py-3">
                @if($matCount === 0)
                    <p class="small mb-0">Open <strong>Step 1</strong> below and upload at least one source (or paste text).</p>
                @elseif(! $allSectionsDone)
                    <p class="small mb-0">Open <strong>Step 2</strong> and generate until every section Aâ€“E is complete.</p>
                @elseif($examCount === 0)
                    <p class="small mb-0">Open <strong>Step 3</strong>, create an assessment, then use <strong>Export QP / Export Ans</strong>.</p>
                @else
                    <p class="small mb-0">Sections are ready and you have assessments. Export from <strong>Step 3</strong> or create another paper.</p>
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
                Step 1 â€” Upload material <span class="badge bg-light text-dark ms-2">{{ $matCount }} source(s)</span>
            </button>
        </h2>
        <div id="qbCollapse1" class="accordion-collapse collapse {{ $openStep === 1 ? 'show' : '' }}" aria-labelledby="qbHeading1" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body">
                <form method="POST" action="{{ route('question-bank.materials.store', $questionBank) }}" enctype="multipart/form-data" class="row g-3">
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
                                        <th scope="col" class="text-nowrap">Text for AI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questionBank->materials as $material)
                                        <tr>
                                            <td class="small">{{ $material->title }}</td>
                                            <td class="small text-nowrap">{{ str_replace('_', ' ', $material->material_type) }}</td>
                                            <td class="small">
                                                @if($material->file_path)
                                                    <a href="{{ Storage::disk('public')->url($material->file_path) }}" target="_blank" rel="noopener" class="text-break">Download file</a>
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
                        <p class="small text-muted mb-0 mt-2">Each row is one material. Use the dropdowns in Step 2 to pick which source to generate from.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="qbHeading2">
            <button class="accordion-button {{ $openStep === 2 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse2" aria-expanded="{{ $openStep === 2 ? 'true' : 'false' }}" aria-controls="qbCollapse2" data-bs-parent="#questionBankAccordion">
                Step 2 â€” Generate &amp; section status
            </button>
        </h2>
        <div id="qbCollapse2" class="accordion-collapse collapse {{ $openStep === 2 ? 'show' : '' }}" aria-labelledby="qbHeading2" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body p-0">
        <div class="card card-landing border-0 shadow-none rounded-0">
            <div class="card-body">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Generate</div>
                        <form method="POST" action="{{ route('question-bank.generate', $questionBank) }}" class="row g-3">
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
                                    <option value="mcq">Multiple Choice</option>
                                    <option value="multi_true_false">Multiple True/False</option>
                                    <option value="matching">Matching Item</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="essay">Essay</option>
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
                        <div class="mt-3 pt-2 border-top">
                            <div class="small text-uppercase text-muted fw-semibold mb-2">All sections (Aâ€“E)</div>
                            <form method="POST" action="{{ route('question-bank.generate-all', $questionBank) }}" class="row g-2 align-items-end">
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
                                    <button type="submit" class="btn btn-primary btn-sm">Generate all missing (fixed Aâ€“E counts)</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Section status</div>
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
                                                        <form method="POST" action="{{ route('question-bank.generate', $questionBank) }}" class="d-inline">
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
                                                        <form method="POST" action="{{ route('question-bank.generate', $questionBank) }}" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="regenerate" value="1">
                                                            <input type="hidden" name="question_material_id" value="{{ $questionBank->materials->first()?->id }}">
                                                            <input type="hidden" name="type" value="{{ $meta['type'] }}">
                                                            <input type="hidden" name="difficulty" value="medium">
                                                            <input type="hidden" name="count" value="{{ min($meta['required'], 20) }}">
                                                            <input type="hidden" name="marks" value="1">
                                                            <button type="button" class="btn btn-warning btn-sm px-2" @if($questionBank->materials->isEmpty()) disabled @endif data-swal-confirm data-swal-title="Regenerate section {{ $section }}?">Regen</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('question-bank.reset-section', $questionBank) }}" class="d-inline">
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
                            <strong>Tip:</strong> Locked = on an assessment. Delete or change that assessment, then regen.
                        </div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="qbHeading3">
            <button class="accordion-button {{ $openStep === 3 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qbCollapse3" aria-expanded="{{ $openStep === 3 ? 'true' : 'false' }}" aria-controls="qbCollapse3" data-bs-parent="#questionBankAccordion">
                Step 3 â€” Build assessment &amp; export <span class="badge bg-light text-dark ms-2">{{ $examCount }} saved</span>
            </button>
        </h2>
        <div id="qbCollapse3" class="accordion-collapse collapse {{ $openStep === 3 ? 'show' : '' }}" aria-labelledby="qbHeading3" data-bs-parent="#questionBankAccordion">
            <div class="accordion-body">
                <div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Build Assessment (Sections A-E)</div>
            <div class="card-body py-3">
                <p class="small text-muted mb-3">
                    After sections Aâ€“E are complete in Step 2, create an assessment here. Export DOCX from the list on the right.
                </p>
                @if(! $allSectionsDone)
                <div class="alert alert-warning small py-2">
                    Complete Step 2 first: each section needs the required question count (A=20, B=4, C=2, D=6, E=2).
                </div>
                @endif
                <form method="POST" action="{{ route('question-bank.exams.store', $questionBank) }}" id="qbCreateAssessmentForm">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><input name="title" class="form-control" placeholder="Title" required></div>
                        <div class="col-md-4">
                            <select name="assessment_type" class="form-select" required>
                                <option value="exam">Exam</option>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input name="exam_type" class="form-control" placeholder="CA I / End of Semester"></div>
                        <div class="col-md-3"><input name="module_code" class="form-control" placeholder="Module code"></div>
                        <div class="col-md-5"><input name="module_name" class="form-control" placeholder="Module name"></div>
                        <div class="col-md-4"><input name="duration_minutes" type="number" class="form-control" placeholder="Duration (mins)"></div>
                        <div class="col-md-4"><input name="examination_number" class="form-control" placeholder="Examination number"></div>
                        <div class="col-md-4"><input name="nactvet_reg_number" class="form-control" placeholder="NACTVET Reg Number"></div>
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
                    <button type="submit" class="btn btn-primary btn-sm mt-1" @if(! $allSectionsDone) disabled title="Complete Step 2 first" @endif>Create Assessment</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        @php
            $qbBulkDelete = [
                'bulkModule' => 'question_bank',
                'bulkAction' => route('question-bank.exams.bulk-destroy', $questionBank),
                'bulkFormId' => 'bulkDeleteQbExams',
                'bulkScopeId' => 'qbExamsList',
                'bulkItemCount' => $examCount,
                'bulkConfirm' => 'Delete :count selected assessment(s)? Question bank items stay; you can build new papers later.',
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
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('question-bank.exams.show', [$questionBank, $exam]) }}">Open</a>
                            <a class="btn btn-sm btn-outline-success" href="{{ route('question-bank.exams.export-docx', [$questionBank, $exam]) }}">Export QP</a>
                            <a class="btn btn-sm btn-outline-success" href="{{ route('question-bank.exams.export-docx', [$questionBank, $exam]) }}?with_answers=1">Export Ans</a>
                            <form method="POST" action="{{ route('question-bank.exams.destroy', [$questionBank, $exam]) }}" class="d-inline-flex align-items-center gap-2" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Delete this exam/assignment/quiz?', text:'Question bank items stay; you can build a new version and export again.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
                                @csrf
                                @method('DELETE')
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="confirm_delete" value="1" id="confirm_del_{{ $exam->id }}" required>
                                    <label class="form-check-label small" for="confirm_del_{{ $exam->id }}">Confirm</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-cohas-delete" title="Delete" aria-label="Delete"><i class="bi bi-trash-fill"></i><span class="ms-1">Delete</span></button>
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
        <p class="mb-2">You have <strong>{{ $purgeableAiCount }}</strong> AI-generated question(s) that are <strong>not</strong> used in any saved assessment. Remove them to free section slots and generate new questions from your notes.</p>
        <p class="small text-muted mb-3">Questions already linked to an exam are kept until you delete that assessment (or remove items manually). Delete an old assessment above if you only want a new paper but the same pool is fine.</p>
        <form method="POST" action="{{ route('question-bank.purge-unused-ai', $questionBank) }}" onsubmit="event.preventDefault(); var f=this; Swal.fire({title:'Remove all unused AI questions?', text:'This removes all unused AI questions from this bank. This cannot be undone.', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d'}).then(function(r){ if(r.isConfirmed) HTMLFormElement.prototype.submit.call(f); });">
            @csrf
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm_purge" value="1" id="confirm_purge_ai" required>
                <label class="form-check-label" for="confirm_purge_ai">I understand these questions will be permanently deleted</label>
            </div>
            <button type="submit" class="btn btn-danger">Remove unused AI questions</button>
        </form>
    </div>
</div>
@endif

<div class="mt-3">{{ $questions->links('pagination::bootstrap-5') }}</div>
@push('scripts')
<script>
(function () {
    var multi = document.querySelector('input[name="material_files[]"]');
    var single = document.querySelector('input[name="material_file"]');
    var out = document.getElementById('qbMaterialFilesPreview');
    if (!out) return;
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
        out.textContent = 'Selected file' + (list.length > 1 ? 's' : '') + ' (' + list.length + '): ' + list.join(' Â· ');
    }
    if (multi) multi.addEventListener('change', render);
    if (single) single.addEventListener('change', render);
})();
</script>
@endpush
@include('partials.bulk-delete.scripts')
@endsection


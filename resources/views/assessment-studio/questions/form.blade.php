@extends('layouts.app')
@section('title', $questionItem ? 'Edit question' : 'Add question')
@section('content')
@php
    $isEdit = (bool) $questionItem;
    $old = fn ($key, $default = null) => old($key, $default);

    // Pre-fill values for edit mode; create mode gets sensible blanks.
    $type = old('type', $questionItem->type ?? 'mcq');
    $difficulty = old('difficulty', $questionItem->difficulty ?? 'medium');
    $marks = old('marks', $questionItem->marks ?? 1);
    $stem = old('stem', $questionItem->stem ?? '');

    $mcqOptions = old('options');
    if (! $mcqOptions) {
        $mcqOptions = $questionItem && $questionItem->type === 'mcq' && is_array($questionItem->options)
            ? array_map(fn ($o) => ['text' => $o['text'] ?? ''], array_values($questionItem->options))
            : array_fill(0, 5, ['text' => '']);
    }
    $mcqCorrect = old('correct_option');
    if ($mcqCorrect === null && $questionItem && $questionItem->type === 'mcq' && is_array($questionItem->options)) {
        foreach (array_values($questionItem->options) as $i => $o) {
            if (! empty($o['is_correct'])) {
                $mcqCorrect = $i;
                break;
            }
        }
    }
    $mcqCorrect = $mcqCorrect ?? 0;

    $mtfStatements = old('statements');
    if (! $mtfStatements) {
        $mtfStatements = $questionItem && $questionItem->type === 'multi_true_false' && is_array($questionItem->options)
            ? array_map(fn ($o) => ['text' => $o['statement'] ?? '', 'answer' => ! empty($o['answer']) ? 1 : 0], array_values($questionItem->options))
            : array_fill(0, 5, ['text' => '', 'answer' => 1]);
    }

    $matchingPairs = old('pairs');
    if (! $matchingPairs) {
        $matchingPairs = $questionItem && $questionItem->type === 'matching' && is_array($questionItem->options) && isset($questionItem->options['pairs'])
            ? array_map(fn ($p) => ['left' => $p['left'] ?? '', 'right' => $p['right'] ?? ''], array_values($questionItem->options['pairs']))
            : array_fill(0, 5, ['left' => '', 'right' => '']);
    }

    $shortAnswerPoints = old('points');
    if (! $shortAnswerPoints) {
        $shortAnswerPoints = $questionItem && $questionItem->type === 'short_answer' && is_array($questionItem->answer_key) && filled($questionItem->answer_key['sample'] ?? null)
            ? array_values(array_filter(array_map('trim', explode(';', $questionItem->answer_key['sample']))))
            : ['', '', '', '', ''];
    }

    $essayParts = old('rubric_parts');
    if (! $essayParts) {
        $essayParts = $questionItem && $questionItem->type === 'essay' && is_array($questionItem->rubric) && ! empty($questionItem->rubric['parts'])
            ? $questionItem->rubric['parts']
            : [
                ['label' => 'Introduction', 'marks' => 3],
                ['label' => 'Main Body (per point)', 'marks' => 2],
                ['label' => 'Conclusion', 'marks' => 2],
            ];
    }
@endphp

<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('assessment-studio.index') }}">Assessment Studio</a>
    <span class="mx-2">/</span>
    <a href="{{ route('assessment-studio.show', $questionBank) }}">{{ $questionBank->name }}</a>
    <span class="mx-2">/</span>
    <span>{{ $isEdit ? 'Edit question' : 'Add question' }}</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-{{ $isEdit ? 'pencil-square' : 'plus-lg' }} me-2 opacity-90"></i>{{ $isEdit ? 'Edit question' : 'Add question' }}</h1>
</div>

@if($isEdit && $questionItem->examItems()->exists())
<div class="alert alert-warning small">This question is used on a saved assessment. Editing it updates the content everywhere it's used, including any already-exported paper.</div>
@endif

<form method="POST" action="{{ $isEdit ? route('assessment-studio.questions.update', [$questionBank, $questionItem]) : route('assessment-studio.questions.store', $questionBank) }}" id="questionForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card card-landing mb-3">
        <div class="card-header-landing"><i class="bi bi-info-circle me-2"></i>Question details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" id="qType" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach($typeLabels as $value => $label)
                            <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select">
                        @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                            <option value="{{ $value }}" {{ $difficulty === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marks</label>
                    <input type="number" step="0.5" min="0.5" name="marks" class="form-control @error('marks') is-invalid @enderror" value="{{ $marks }}" required>
                    @error('marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Question stem</label>
                    <textarea name="stem" rows="3" class="form-control @error('stem') is-invalid @enderror" required>{{ $stem }}</textarea>
                    @error('stem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card card-landing mb-3" data-type-panel="mcq">
        <div class="card-header-landing"><i class="bi bi-list-check me-2"></i>Multiple choice options</div>
        <div class="card-body">
            <p class="small text-muted">Fill all five options (A–E) and mark the correct one.</p>
            @foreach($mcqOptions as $i => $option)
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-auto"><span class="badge bg-light text-dark">{{ chr(65 + $i) }}</span></div>
                    <div class="col">
                        <input type="text" name="options[{{ $i }}][text]" class="form-control form-control-sm" value="{{ $option['text'] }}" placeholder="Option {{ chr(65 + $i) }}">
                    </div>
                    <div class="col-auto form-check">
                        <input class="form-check-input" type="radio" name="correct_option" value="{{ $i }}" id="mcqCorrect{{ $i }}" {{ (int) $mcqCorrect === $i ? 'checked' : '' }}>
                        <label class="form-check-label small" for="mcqCorrect{{ $i }}">Correct</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-landing mb-3" data-type-panel="multi_true_false">
        <div class="card-header-landing"><i class="bi bi-toggle2-on me-2"></i>True/False statements</div>
        <div class="card-body">
            <p class="small text-muted">Five sub-statements (i–v), each marked True or False.</p>
            @foreach($mtfStatements as $i => $statement)
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-auto"><span class="badge bg-light text-dark">{{ ['i', 'ii', 'iii', 'iv', 'v'][$i] ?? $i + 1 }}</span></div>
                    <div class="col">
                        <input type="text" name="statements[{{ $i }}][text]" class="form-control form-control-sm" value="{{ $statement['text'] }}" placeholder="Statement">
                    </div>
                    <div class="col-auto" style="min-width: 110px">
                        <select name="statements[{{ $i }}][answer]" class="form-select form-select-sm">
                            <option value="1" {{ (int) $statement['answer'] === 1 ? 'selected' : '' }}>TRUE</option>
                            <option value="0" {{ (int) $statement['answer'] === 0 ? 'selected' : '' }}>FALSE</option>
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-landing mb-3" data-type-panel="matching">
        <div class="card-header-landing"><i class="bi bi-shuffle me-2"></i>Matching pairs</div>
        <div class="card-body">
            <p class="small text-muted">Up to five Column A / Column B pairs. Export automatically fills extra Column B distractors.</p>
            @foreach($matchingPairs as $i => $pair)
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-auto"><span class="badge bg-light text-dark">{{ $i + 1 }}</span></div>
                    <div class="col">
                        <input type="text" name="pairs[{{ $i }}][left]" class="form-control form-control-sm" value="{{ $pair['left'] }}" placeholder="Column A term">
                    </div>
                    <div class="col">
                        <input type="text" name="pairs[{{ $i }}][right]" class="form-control form-control-sm" value="{{ $pair['right'] }}" placeholder="Column B match">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-landing mb-3" data-type-panel="short_answer">
        <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Expected points</div>
        <div class="card-body">
            <p class="small text-muted">The list of points a full-mark answer should cover.</p>
            <div id="pointsList">
                @foreach($shortAnswerPoints as $point)
                    <div class="input-group input-group-sm mb-2 points-row">
                        <input type="text" name="points[]" class="form-control" value="{{ $point }}" placeholder="Expected point">
                        <button type="button" class="btn btn-outline-danger points-remove"><i class="bi bi-dash-lg"></i></button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="pointsAdd" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add point</button>
        </div>
    </div>

    <div class="card card-landing mb-3" data-type-panel="essay">
        <div class="card-header-landing"><i class="bi bi-file-text me-2"></i>Marking rubric</div>
        <div class="card-body">
            <p class="small text-muted">Break the rubric into named parts with marks each, e.g. Introduction, Main Body (per point), Conclusion.</p>
            <div id="rubricList">
                @foreach($essayParts as $i => $part)
                    <div class="row g-2 align-items-center mb-2 rubric-row">
                        <div class="col">
                            <input type="text" name="rubric_parts[{{ $i }}][label]" class="form-control form-control-sm" value="{{ $part['label'] }}" placeholder="Part label">
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.5" min="0" name="rubric_parts[{{ $i }}][marks]" class="form-control form-control-sm" value="{{ $part['marks'] }}" placeholder="Marks">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm rubric-remove"><i class="bi bi-dash-lg"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" id="rubricAdd" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add part</button>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save question</button>
        <a href="{{ route('assessment-studio.show', $questionBank) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('qType');
    var panels = document.querySelectorAll('[data-type-panel]');

    function syncPanels() {
        var current = typeSelect.value;
        panels.forEach(function (panel) {
            panel.classList.toggle('d-none', panel.getAttribute('data-type-panel') !== current);
        });
    }
    typeSelect.addEventListener('change', syncPanels);
    syncPanels();

    function wireRemovableList(containerId, addBtnId, removeClass, rowClass, rowHtml) {
        var container = document.getElementById(containerId);
        var addBtn = document.getElementById(addBtnId);
        if (!container || !addBtn) return;
        addBtn.addEventListener('click', function () {
            container.insertAdjacentHTML('beforeend', rowHtml);
        });
        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.' + removeClass);
            if (!btn) return;
            var row = btn.closest('.' + rowClass);
            if (row && container.querySelectorAll('.' + rowClass).length > 1) {
                row.remove();
            }
        });
    }

    wireRemovableList('pointsList', 'pointsAdd', 'points-remove', 'points-row',
        '<div class="input-group input-group-sm mb-2 points-row">' +
        '<input type="text" name="points[]" class="form-control" placeholder="Expected point">' +
        '<button type="button" class="btn btn-outline-danger points-remove"><i class="bi bi-dash-lg"></i></button>' +
        '</div>');

    wireRemovableList('rubricList', 'rubricAdd', 'rubric-remove', 'rubric-row',
        '<div class="row g-2 align-items-center mb-2 rubric-row">' +
        '<div class="col"><input type="text" name="rubric_parts[][label]" class="form-control form-control-sm" placeholder="Part label"></div>' +
        '<div class="col-3"><input type="number" step="0.5" min="0" name="rubric_parts[][marks]" class="form-control form-control-sm" placeholder="Marks"></div>' +
        '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm rubric-remove"><i class="bi bi-dash-lg"></i></button></div>' +
        '</div>');
});
</script>
@endpush
@endsection

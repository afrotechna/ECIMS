<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Http\Controllers\Concerns\HasNactvetSectionMap;
use App\Models\Course;
use App\Models\ExamPaper;
use App\Models\ExamPaperItem;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionMaterial;
use App\Services\QuestionGeneration\QuestionGeneratorContract;
use App\Support\PdfTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive as PhpWordZipArchive;
use ZipArchive as PhpZipArchive;

class QuestionBankController extends Controller
{
    use BulkDestroysRecords;
    use HasNactvetSectionMap;

    public function index()
    {
        $hodProgrammeId = auth()->user()->hodProgrammeId();

        $banks = QuestionBank::with(['course', 'creator'])
            ->withCount(['materials', 'questions', 'exams'])
            ->when($hodProgrammeId, fn ($q, $pid) => $q->whereHas('course', fn ($cq) => $cq->where('programme_id', $pid)))
            ->latest()
            ->paginate(15);

        $courses = Course::where('is_active', true)
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->orderBy('code')->get();

        return view('assessment-studio.index', compact('banks', 'courses'));
    }

    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $validated['created_by'] = auth()->id();
        QuestionBank::create($validated);

        return redirect()->route('assessment-studio.index')->with('success', 'Bank created.');
    }

    public function show(QuestionBank $questionBank)
    {
        $questionBank->load([
            'course',
            'materials' => fn ($q) => $q->with('uploader')->latest('id'),
        ]);
        $questions = $questionBank->questions()->withCount('examItems')->latest()->paginate(20);
        $exams = $questionBank->exams()->latest()->take(10)->get();
        $requiredBySection = $this->requiredCountsBySection();
        $typeBySection = $this->sectionTypeMap();
        $labelsBySection = ['A' => 'Multiple Choice', 'B' => 'Multiple True/False', 'C' => 'Matching', 'D' => 'Short Answer', 'E' => 'Guided Essay'];
        $existingByType = $questionBank->questions()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $sectionProgress = [];
        foreach ($requiredBySection as $section => $required) {
            $type = $typeBySection[$section];
            $existing = (int) ($existingByType[$type] ?? 0);
            $sectionProgress[$section] = [
                'type' => $type,
                'label' => $labelsBySection[$section],
                'required' => $required,
                'existing' => $existing,
                'remaining' => max(0, $required - $existing),
                'done' => $existing >= $required,
            ];
        }

        $purgeableAiCount = QuestionItem::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('is_ai_generated', true)
            ->whereDoesntHave('examItems')
            ->count();

        $purgeableByType = QuestionItem::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('is_ai_generated', true)
            ->whereDoesntHave('examItems')
            ->selectRaw('type, count(*) as c')
            ->groupBy('type')
            ->pluck('c', 'type')
            ->map(fn ($n) => (int) $n)
            ->all();

        $lockedByType = QuestionItem::query()
            ->where('question_bank_id', $questionBank->id)
            ->whereHas('examItems')
            ->selectRaw('type, count(*) as c')
            ->groupBy('type')
            ->pluck('c', 'type')
            ->map(fn ($n) => (int) $n)
            ->all();

        foreach ($sectionProgress as $section => &$meta) {
            $meta['locked'] = (int) ($lockedByType[$meta['type']] ?? 0);
            $meta['resettable'] = (int) ($purgeableByType[$meta['type']] ?? 0);
            $meta['can_add'] = $meta['remaining'] > 0;
            $meta['can_regenerate'] = $meta['resettable'] > 0;
            $meta['is_blocked'] = $meta['remaining'] === 0 && $meta['resettable'] === 0 && $meta['locked'] > 0;
        }
        unset($meta);

        $typeLabels = $this->questionTypeLabels();

        return view('assessment-studio.show', compact('questionBank', 'questions', 'exams', 'sectionProgress', 'purgeableAiCount', 'purgeableByType', 'typeLabels'));
    }

    public function uploadMaterial(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'material_type' => ['required', 'in:notes,assessment_plan,curriculum,material'],
            'material_file' => ['nullable', 'file', 'mimes:txt,pdf,doc,docx', 'max:10240'],
            'material_files' => ['nullable', 'array', 'max:25'],
            'material_files.*' => ['file', 'mimes:txt,pdf,doc,docx', 'max:10240'],
            'text_content' => ['nullable', 'string'],
        ]);

        $files = [];
        if ($request->hasFile('material_files')) {
            foreach ($request->file('material_files', []) as $file) {
                if ($file && $file->isValid()) {
                    $files[] = $file;
                }
            }
        }
        if ($request->hasFile('material_file')) {
            $files[] = $request->file('material_file');
        }

        $textContent = isset($validated['text_content']) ? trim((string) $validated['text_content']) : '';
        if ($files === [] && $textContent === '') {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->withInput()
                ->withErrors(['material_file' => 'Add at least one file or paste text content.']);
        }

        $created = 0;
        $pdfExtractWarnings = [];

        if ($files !== []) {
            foreach ($files as $uploadedFile) {
                $path = $uploadedFile->store('question-bank/materials', 'public');
                $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $title = count($files) > 1
                    ? Str::limit($validated['title'].' – '.$original, 255, '')
                    : $validated['title'];
                $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
                $absolutePath = Storage::disk('public')->path($path);
                $extracted = $this->extractTextFromStoredFile($absolutePath, $extension);
                $extracted = $this->sanitizeUtf8($extracted);

                if ($extension === 'pdf' && $extracted === '') {
                    $pdfExtractWarnings[] = $title;
                }

                QuestionMaterial::create([
                    'question_bank_id' => $questionBank->id,
                    'uploaded_by' => auth()->id(),
                    'title' => $title,
                    'material_type' => $validated['material_type'],
                    'file_path' => $path,
                    'text_content' => $extracted !== '' ? $extracted : null,
                ]);
                $created++;
            }
        }

        if ($textContent !== '') {
            QuestionMaterial::create([
                'question_bank_id' => $questionBank->id,
                'uploaded_by' => auth()->id(),
                'title' => $validated['title'].(count($files) > 0 ? ' (pasted text)' : ''),
                'material_type' => $validated['material_type'],
                'file_path' => null,
                'text_content' => $textContent,
            ]);
            $created++;
        }

        $message = $created === 1 ? 'Material uploaded.' : "{$created} materials uploaded.";
        $redirect = redirect()->route('assessment-studio.show', $questionBank)->with('success', $message);

        if ($pdfExtractWarnings !== []) {
            $redirect->with(
                'warning',
                'No readable text was extracted from PDF: '.implode(', ', $pdfExtractWarnings).
                '. Use a text-based PDF (not a scanned image), install Poppler and add pdftotext to your PATH, or paste plain text in the material form.'
            );
        }

        return $redirect;
    }

    public function generate(Request $request, QuestionBank $questionBank, QuestionGeneratorContract $generationService)
    {
        $validated = $request->validate([
            'question_material_id' => ['required', 'exists:question_materials,id'],
            'type' => ['required', 'in:mcq,multi_true_false,matching,short_answer,essay'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'marks' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'regenerate' => ['nullable', 'boolean'],
        ]);

        $material = QuestionMaterial::where('question_bank_id', $questionBank->id)
            ->findOrFail($validated['question_material_id']);

        $text = $this->extractGenerationText($material);

        if (trim($text) === '') {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', $this->materialExtractionFailedMessage());
        }

        $maxPerType = array_flip($this->sectionTypeMap());
        $requiredCap = $this->requiredCountsByType();

        $removedDuringRegen = 0;
        if ($request->boolean('regenerate')) {
            $removedDuringRegen = $this->purgeUnusedAiForType($questionBank, $validated['type']);
        }

        $generateCount = (int) $validated['count'];
        $generatedItems = $generationService->generate($validated['type'], $text, $generateCount, $validated['difficulty']);
        $currentCount = (int) QuestionItem::where('question_bank_id', $questionBank->id)
            ->where('type', $validated['type'])
            ->count();
        $remaining = max(0, ($requiredCap[$validated['type']] ?? 9999) - $currentCount);
        if ($remaining === 0) {
            $section = $maxPerType[$validated['type']] ?? '?';
            $hint = $request->boolean('regenerate')
                ? ' No unused AI questions could be removed (they may all be on an exam). Delete or edit an assessment, or use per-section reset if available.'
                : ' Try “Regenerate” to replace unused AI items first.';

            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', "Section {$section} is already full.{$hint}");
        }
        $generatedItems = array_slice($generatedItems, 0, $remaining);
        $marks = (float) ($validated['marks'] ?? 1);

        DB::transaction(function () use ($generatedItems, $questionBank, $material, $marks) {
            foreach ($generatedItems as $item) {
                QuestionItem::create([
                    'question_bank_id' => $questionBank->id,
                    'course_id' => $questionBank->course_id,
                    'question_material_id' => $material->id,
                    'type' => $item['type'],
                    'difficulty' => $item['difficulty'],
                    'stem' => $item['stem'],
                    'options' => $item['options'],
                    'answer_key' => $item['answer_key'],
                    'rubric' => $item['rubric'],
                    'marks' => $marks,
                    'is_ai_generated' => true,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        if ($request->boolean('regenerate')) {
            $msg = $removedDuringRegen > 0
                ? "Removed {$removedDuringRegen} unused AI question(s) and added new ones."
                : 'New questions were added (there were no unused AI questions to remove for that type).';
        } else {
            $msg = 'Questions generated and added to bank.';
        }

        return redirect()->route('assessment-studio.show', $questionBank)->with('success', $msg);
    }

    /**
     * Generate all missing fixed sections (A-E) in one action.
     */
    public function generateAllSections(Request $request, QuestionBank $questionBank, QuestionGeneratorContract $generationService)
    {
        $validated = $request->validate([
            'question_material_id' => ['required', 'exists:question_materials,id'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'marks' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'regenerate' => ['nullable', 'boolean'],
        ]);

        $material = QuestionMaterial::where('question_bank_id', $questionBank->id)
            ->findOrFail($validated['question_material_id']);

        $text = $this->extractGenerationText($material);
        if (trim($text) === '') {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', $this->materialExtractionFailedMessage());
        }

        $requiredCap = $this->requiredCountsByType();
        $marks = (float) ($validated['marks'] ?? 1);
        $removedTotal = 0;
        $addedTotal = 0;
        $addedByType = [];

        foreach ($requiredCap as $type => $required) {
            if ($request->boolean('regenerate')) {
                $removedTotal += $this->purgeUnusedAiForType($questionBank, $type);
            }

            $currentCount = (int) QuestionItem::where('question_bank_id', $questionBank->id)
                ->where('type', $type)
                ->count();
            $remaining = max(0, $required - $currentCount);
            if ($remaining === 0) {
                continue;
            }

            $generatedItems = $generationService->generate($type, $text, $remaining, $validated['difficulty']);
            $generatedItems = array_slice($generatedItems, 0, $remaining);

            DB::transaction(function () use ($generatedItems, $questionBank, $material, $marks, $type, &$addedTotal, &$addedByType) {
                foreach ($generatedItems as $item) {
                    QuestionItem::create([
                        'question_bank_id' => $questionBank->id,
                        'course_id' => $questionBank->course_id,
                        'question_material_id' => $material->id,
                        'type' => $item['type'] ?? $type,
                        'difficulty' => $item['difficulty'] ?? 'medium',
                        'stem' => $item['stem'],
                        'options' => $item['options'] ?? null,
                        'answer_key' => $item['answer_key'] ?? null,
                        'rubric' => $item['rubric'] ?? null,
                        'marks' => $marks,
                        'is_ai_generated' => true,
                        'created_by' => auth()->id(),
                    ]);
                    $addedTotal++;
                    $addedByType[$type] = ($addedByType[$type] ?? 0) + 1;
                }
            });
        }

        if ($addedTotal === 0) {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('info', 'All sections are already full. Delete or edit assessments, then regenerate if you need replacement questions.');
        }

        $parts = [];
        foreach ($addedByType as $type => $count) {
            $parts[] = "{$type}: {$count}";
        }
        $summary = implode(', ', $parts);
        $prefix = $request->boolean('regenerate') ? "Regenerated (removed {$removedTotal}) and added {$addedTotal} question(s)." : "Added {$addedTotal} question(s).";

        return redirect()->route('assessment-studio.show', $questionBank)
            ->with('success', "{$prefix} {$summary}");
    }

    /** Remove unused AI-generated questions for one section type only (not linked to any exam). */
    public function resetSectionQuestions(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:mcq,multi_true_false,matching,short_answer,essay'],
            'confirm_reset' => ['accepted'],
        ]);

        $deleted = $this->purgeUnusedAiForType($questionBank, $validated['type']);
        $section = array_flip($this->sectionTypeMap())[$validated['type']] ?? '?';

        if ($deleted === 0) {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', "Section {$section}: nothing to reset (no unused AI questions, or they are used on an exam).");
        }

        return redirect()->route('assessment-studio.show', $questionBank)
            ->with('success', "Section {$section}: removed {$deleted} unused AI question(s). Generate again to refill.");
    }

    public function createExam(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'assessment_type' => ['required', 'in:exam,quiz,assignment'],
            'exam_type' => ['nullable', 'string', 'max:100'],
            'module_code' => ['nullable', 'string', 'max:100'],
            'module_name' => ['nullable', 'string', 'max:255'],
            'nactvet_reg_number' => ['nullable', 'string', 'max:100'],
            'examination_number' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:720'],
            'marks_a' => ['required', 'numeric', 'min:0'],
            'marks_b' => ['required', 'numeric', 'min:0'],
            'marks_c' => ['required', 'numeric', 'min:0'],
            'marks_d' => ['required', 'numeric', 'min:0'],
            'marks_e' => ['required', 'numeric', 'min:0'],
        ]);

        $sectionMap = $this->sectionTypeMap();
        $requestedCounts = $this->requiredCountsBySection();
        $sectionMarks = [
            'A' => (float) $validated['marks_a'],
            'B' => (float) $validated['marks_b'],
            'C' => (float) $validated['marks_c'],
            'D' => (float) $validated['marks_d'],
            'E' => (float) $validated['marks_e'],
        ];
        $instructions = [
            'A' => 'Answer item (i) to (xx) by choosing the letter of the most correct answer and write it in the box provided using CAPITAL letters.',
            'B' => 'This section consists of FOUR (4) questions with FIVE (5) options each. Write TRUE/FALSE in CAPITAL letters for (i)-(v).',
            'C' => 'This section consists of TWO (2) matching questions with FIVE (5) options each in Part A and Part B. Use CAPITAL letters.',
            'D' => 'This section consists of SIX (6) questions. Answer each question by writing FIVE (5) points in the provided space.',
            'E' => 'This section consists of TWO (2) guided essay questions. Answer in narrative form and start each question on a new page.',
        ];
        $generalInstructions = implode(' ', [
            'Read all instructions carefully.',
            'Write your registration number on each page you use.',
            'This paper consists of five (5) sections, A, B, C, D and E.',
            'Attempt all questions. The responses should be written in the spaces provided.',
            'Each essay question to be answered on not more than 2 hand written pages.',
            'Failure to follow instructions will lead to loss of marks.',
            'Cellular phones and unauthorized materials are NOT allowed in the examination room.',
        ]);

        if (array_sum($sectionMarks) <= 0) {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', 'Allocate marks for at least one section.');
        }

        $selectedBySection = [];
        foreach ($sectionMap as $section => $questionType) {
            $need = $requestedCounts[$section];
            $marks = $sectionMarks[$section] ?? 0.0;
            if ($need < 1 || $marks <= 0) {
                continue;
            }

            $picked = QuestionItem::query()
                ->where('question_bank_id', $questionBank->id)
                ->where('type', $questionType)
                ->whereDoesntHave('examItems')
                ->latest()
                ->limit($need)
                ->get();

            if ($picked->count() < $need) {
                $picked = QuestionItem::query()
                    ->where('question_bank_id', $questionBank->id)
                    ->where('type', $questionType)
                    ->latest()
                    ->limit($need)
                    ->get();
            }

            if ($picked->count() < $need) {
                return redirect()->route('assessment-studio.show', $questionBank)
                    ->with('error', "Section {$section} needs {$need} {$questionType} question(s), but only {$picked->count()} are available. Generate questions in Step 2 first.");
            }

            $selectedBySection[$section] = $picked;
        }

        if ($selectedBySection === []) {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', 'Allocate marks for at least one section (A–E) with available questions.');
        }

        $exam = DB::transaction(function () use ($questionBank, $validated, $selectedBySection, $instructions, $sectionMarks, $generalInstructions) {
            $exam = ExamPaper::create([
                'question_bank_id' => $questionBank->id,
                'course_id' => $questionBank->course_id,
                'created_by' => auth()->id(),
                'assessment_type' => $validated['assessment_type'],
                'exam_type' => $validated['exam_type'] ?? null,
                'module_code' => $validated['module_code'] ?? ($questionBank->course?->code ?? null),
                'module_name' => $validated['module_name'] ?? ($questionBank->course?->name ?? null),
                'nactvet_reg_number' => $validated['nactvet_reg_number'] ?? null,
                'examination_number' => $validated['examination_number'] ?? null,
                'title' => $validated['title'],
                'instructions' => $generalInstructions,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'total_marks' => array_sum($sectionMarks),
            ]);

            $order = 1;
            foreach (['A', 'B', 'C', 'D', 'E'] as $sectionLabel) {
                $questions = $selectedBySection[$sectionLabel] ?? collect();
                $perQuestionMark = $questions->count() > 0 ? ($sectionMarks[$sectionLabel] / $questions->count()) : 0;
                foreach ($questions->values() as $question) {
                    ExamPaperItem::create([
                        'exam_paper_id' => $exam->id,
                        'question_item_id' => $question->id,
                        'section_label' => $sectionLabel,
                        'section_instruction' => $instructions[$sectionLabel],
                        'question_order' => $order++,
                        'marks' => $perQuestionMark,
                    ]);
                }
            }

            return $exam;
        });

        return redirect()->route('assessment-studio.exams.show', [$questionBank, $exam])->with('success', 'Exam created.');
    }

    /**
     * Build a quiz or assignment: no fixed A-E section structure, just a chosen
     * mix of question types/counts/marks. The rigid exam path above is untouched.
     */
    public function createFlexibleAssessment(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'assessment_type' => ['required', 'in:quiz,assignment'],
            'exam_type' => ['nullable', 'string', 'max:100'],
            'module_code' => ['nullable', 'string', 'max:100'],
            'module_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:720'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:mcq,multi_true_false,matching,short_answer,essay'],
            'items.*.count' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.marks_each' => ['required', 'numeric', 'min:0.5', 'max:100'],
        ]);

        $typeLabels = $this->questionTypeLabels();
        $selectedByType = [];
        foreach ($validated['items'] as $row) {
            $type = $row['type'];
            $need = (int) $row['count'];

            $picked = QuestionItem::query()
                ->where('question_bank_id', $questionBank->id)
                ->where('type', $type)
                ->whereDoesntHave('examItems')
                ->latest()
                ->limit($need)
                ->get();

            if ($picked->count() < $need) {
                $picked = QuestionItem::query()
                    ->where('question_bank_id', $questionBank->id)
                    ->where('type', $type)
                    ->latest()
                    ->limit($need)
                    ->get();
            }

            if ($picked->count() < $need) {
                $label = $typeLabels[$type] ?? $type;

                return redirect()->route('assessment-studio.show', $questionBank)
                    ->with('error', "Need {$need} {$label} question(s), but only {$picked->count()} are available. Add or generate more first.");
            }

            $selectedByType[] = ['type' => $type, 'marks_each' => (float) $row['marks_each'], 'questions' => $picked];
        }

        $totalMarks = collect($selectedByType)->sum(fn ($row) => $row['marks_each'] * $row['questions']->count());

        $exam = DB::transaction(function () use ($questionBank, $validated, $selectedByType, $typeLabels, $totalMarks) {
            $exam = ExamPaper::create([
                'question_bank_id' => $questionBank->id,
                'course_id' => $questionBank->course_id,
                'created_by' => auth()->id(),
                'assessment_type' => $validated['assessment_type'],
                'exam_type' => $validated['exam_type'] ?? null,
                'module_code' => $validated['module_code'] ?? ($questionBank->course?->code ?? null),
                'module_name' => $validated['module_name'] ?? ($questionBank->course?->name ?? null),
                'title' => $validated['title'],
                'instructions' => 'Answer all questions.',
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'total_marks' => $totalMarks,
            ]);

            $order = 1;
            foreach ($selectedByType as $row) {
                $count = $row['questions']->count();
                $label = $typeLabels[$row['type']] ?? $row['type'];
                $instruction = $count === 1
                    ? "Answer this {$label} question."
                    : "Answer all {$count} {$label} questions.";

                foreach ($row['questions']->values() as $question) {
                    ExamPaperItem::create([
                        'exam_paper_id' => $exam->id,
                        'question_item_id' => $question->id,
                        'section_label' => null,
                        'section_instruction' => $instruction,
                        'question_order' => $order++,
                        'marks' => $row['marks_each'],
                    ]);
                }
            }

            return $exam;
        });

        return redirect()->route('assessment-studio.exams.show', [$questionBank, $exam])->with('success', ucfirst($validated['assessment_type']).' created.');
    }

    public function showExam(QuestionBank $questionBank, ExamPaper $examPaper)
    {
        abort_unless($examPaper->question_bank_id === $questionBank->id, 404);

        $examPaper->load(['items.question']);

        return view('assessment-studio.exam-show', compact('questionBank', 'examPaper'));
    }

    public function destroyExam(Request $request, QuestionBank $questionBank, ExamPaper $examPaper)
    {
        abort_unless($examPaper->question_bank_id === $questionBank->id, 404);
        $request->validate([
            'confirm_delete' => ['accepted'],
        ]);

        $examPaper->delete();

        return redirect()->route('assessment-studio.show', $questionBank)
            ->with('success', 'Assessment removed. Bank items are unchanged; create a new assessment when ready.');
    }

    public function bulkDestroyExams(Request $request, QuestionBank $questionBank)
    {
        return $this->bulkDestroyRecords(
            $request,
            ExamPaper::class,
            'assessment-studio.show',
            fn () => ['questionBank' => $questionBank],
            singularLabel: 'assessment',
            deleter: function (ExamPaper $exam) use ($questionBank) {
                if ($exam->question_bank_id !== $questionBank->id) {
                    return false;
                }
                $exam->delete();

                return true;
            },
        );
    }

    /**
     * Remove AI-generated questions that are not linked to any exam paper so you can generate fresh items.
     */
    public function purgeUnusedAiQuestions(Request $request, QuestionBank $questionBank)
    {
        $request->validate([
            'confirm_purge' => ['accepted'],
        ]);

        $deleted = QuestionItem::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('is_ai_generated', true)
            ->whereDoesntHave('examItems')
            ->delete();

        return redirect()->route('assessment-studio.show', $questionBank)
            ->with('success', "Removed {$deleted} unused AI-generated question(s). Sections can be filled again.");
    }

    public function exportDocx(QuestionBank $questionBank, ExamPaper $examPaper)
    {
        abort_unless($examPaper->question_bank_id === $questionBank->id, 404);
        $examPaper->load(['items.question']);
        $withAnswers = request()->boolean('with_answers');

        if (! extension_loaded('zip') || ! class_exists(PhpZipArchive::class)) {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $phpWord = new PhpWord;
        $this->configureDocumentStyles($phpWord);
        $section = $phpWord->addSection($this->sectionLayout());
        $this->buildCoverPage($section, $examPaper);
        $this->addHeaderFooter($section, $examPaper);
        $section->addPageBreak();
        $this->buildQuestionBody($section, $examPaper, $withAnswers);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempFile = $tempDir.DIRECTORY_SEPARATOR.uniqid('exam_docx_', true).'.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $suffix = $withAnswers ? '-answer-guide' : '-question-paper';
        $fileName = str($examPaper->title)->slug('-').$suffix.'.docx';

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function purgeUnusedAiForType(QuestionBank $questionBank, string $type): int
    {
        return QuestionItem::query()
            ->where('question_bank_id', $questionBank->id)
            ->where('type', $type)
            ->where('is_ai_generated', true)
            ->whereDoesntHave('examItems')
            ->delete();
    }

    private function buildCoverPage($section, ExamPaper $examPaper): void
    {
        $section->addText('MINISTRY OF HEALTH', 'exam_heading', 'exam_center_single');
        $section->addText('MUSOMA CLINICAL OFFICER TRAINING CENTRE', 'exam_heading', 'exam_center_single');
        $section->addTextBreak();
        $section->addText('BASIC TECHNICIAN CERTIFICATE (NTA LEVEL 4) IN CLINICAL MEDICINE', 'exam_heading', 'exam_center_single');
        $assessmentTitle = $examPaper->exam_type
            ? strtoupper($examPaper->exam_type)
            : strtoupper(str_replace('_', ' ', $examPaper->assessment_type ?? 'exam').' examination');
        $section->addText($assessmentTitle, 'exam_heading', 'exam_center_single');
        $section->addTextBreak();
        $section->addText('MODULE CODE: '.strtoupper($examPaper->module_code ?: '-'), 'exam_body_bold', 'exam_left_single');
        $section->addText('MODULE NAME: '.strtoupper($examPaper->module_name ?: '-'), 'exam_body_bold', 'exam_left_single');
        $section->addText($this->examSessionMonthYear($examPaper), 'exam_body_bold', 'exam_center_single');
        $section->addText(
            'TIME: '.$this->formatExamDurationLabel($examPaper->duration_minutes).' DATE……………………….',
            'exam_body',
            'exam_left_single'
        );
        $section->addTextBreak();
        $section->addText('GENERAL INSTRUCTIONS:', 'exam_body_bold', 'exam_left_single');
        $n = 1;
        foreach ($this->coverGeneralInstructionLines($examPaper) as $rule) {
            $section->addText($n++.'. '.$rule, 'exam_body', 'exam_left_single');
        }
        $section->addTextBreak();
        $section->addText('The table below is for OFFICIAL USE ONLY', 'exam_body_bold', 'exam_left_single');
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $table->addRow();
        $this->addOfficialUseHeaderCell($table->addCell(900), 'SECTION');
        $this->addOfficialUseHeaderCell($table->addCell(2600), 'TYPE OF QUESTIONS');
        $this->addOfficialUseHeaderCell($table->addCell(1100), "ALLOCATED\nMARKS");
        $this->addOfficialUseHeaderCell($table->addCell(1100), "SCORED\nMARKS");
        $this->addOfficialUseHeaderCell($table->addCell(1400), "INITIAL SIGNATURE\nOF MARKER");
        $this->addOfficialUseHeaderCell($table->addCell(1400), "INITIAL SIGNATURE\nOF VERIFIER");
        if ($examPaper->assessment_type === 'exam') {
            $sectionMarks = $this->sectionMarksFromItems($examPaper);
            foreach (['A' => 'MULTIPLE CHOICE', 'B' => 'MULTIPLE TRUE/FALSE', 'C' => 'MATCHING ITEM', 'D' => 'SHORT ANSWER', 'E' => 'GUIDED ESSAY'] as $sec => $label) {
                $table->addRow();
                $table->addCell(900)->addText($sec, 'exam_body', ['alignment' => 'center']);
                $table->addCell(2600)->addText($label, 'exam_body', ['alignment' => 'left']);
                $table->addCell(1100)->addText((string) (int) round($sectionMarks[$sec] ?? 0), 'exam_body', ['alignment' => 'center']);
                $table->addCell(1100)->addText('');
                $table->addCell(1400)->addText('');
                $table->addCell(1400)->addText('');
            }
        } else {
            $typeLabels = ['mcq' => 'MULTIPLE CHOICE', 'multi_true_false' => 'MULTIPLE TRUE/FALSE', 'matching' => 'MATCHING ITEM', 'short_answer' => 'SHORT ANSWER', 'essay' => 'GUIDED ESSAY'];
            $marksByType = [];
            foreach ($examPaper->items as $item) {
                $type = $item->question->type ?? 'mcq';
                $marksByType[$type] = ($marksByType[$type] ?? 0) + (float) $item->marks;
            }
            foreach ($marksByType as $type => $marks) {
                $table->addRow();
                $table->addCell(900)->addText('', 'exam_body', ['alignment' => 'center']);
                $table->addCell(2600)->addText($typeLabels[$type] ?? strtoupper($type), 'exam_body', ['alignment' => 'left']);
                $table->addCell(1100)->addText((string) (int) round($marks), 'exam_body', ['alignment' => 'center']);
                $table->addCell(1100)->addText('');
                $table->addCell(1400)->addText('');
                $table->addCell(1400)->addText('');
            }
        }
        $table->addRow();
        $table->addCell(900)->addText('');
        $table->addCell(2600)->addText('TOTAL', 'exam_body_bold', ['alignment' => 'right']);
        $table->addCell(1100)->addText((string) (int) round((float) $examPaper->total_marks), 'exam_body_bold', ['alignment' => 'center']);
        $table->addCell(1100)->addText('');
        $table->addCell(1400)->addText('');
        $table->addCell(1400)->addText('');
    }

    private function addOfficialUseHeaderCell($cell, string $text): void
    {
        foreach (preg_split("/\n/", $text) as $i => $line) {
            if ($i > 0) {
                $cell->addTextBreak();
            }
            $cell->addText($line, 'exam_body_bold', ['alignment' => 'center']);
        }
    }

    private function coverGeneralInstructionLines(ExamPaper $examPaper): array
    {
        $raw = trim((string) $examPaper->instructions);
        if ($raw === '') {
            return [
                'Read all instructions carefully.',
                'This paper consists of FIVE (5) sections.',
                'Attempt all questions.',
                'Write your examination number on each page of the answer sheet you use.',
                'Each essay question to be answered on not more than 3 hand-written papers',
            ];
        }
        $lines = preg_split('/\R/u', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $out[] = preg_replace('/^\d+\.\s*/', '', $line);
            }
        }

        return $out !== [] ? $out : [
            'Read all instructions carefully.',
            'This paper consists of FIVE (5) sections.',
            'Attempt all questions.',
        ];
    }

    private function examSessionMonthYear(ExamPaper $examPaper): string
    {
        $d = $examPaper->created_at ?? now();

        return strtoupper($d->format('F, Y'));
    }

    /** Month + year for running header line (e.g. November 2024), matching official papers. */
    private function examSessionMonthYearRunning(ExamPaper $examPaper): string
    {
        $d = $examPaper->created_at ?? now();

        return $d->format('F Y');
    }

    /** Full running header text before the PAGE field: CODE: Name; Exam type, Month Year Page */
    private function examRunningHeaderBeforePage(ExamPaper $examPaper): string
    {
        $moduleCode = strtoupper((string) ($examPaper->module_code ?: '-'));
        $moduleName = (string) ($examPaper->module_name ?: '-');
        $nature = $examPaper->exam_type
            ? (string) $examPaper->exam_type
            : Str::title(str_replace('_', ' ', $examPaper->assessment_type ?? 'examination'));

        return sprintf(
            '%s: %s; %s, %s Page ',
            $moduleCode,
            $moduleName,
            $nature,
            $this->examSessionMonthYearRunning($examPaper)
        );
    }

    /** Spelled counts for instructions, e.g. Twenty (20). */
    private function examSpelledCount(int $n): string
    {
        return match ($n) {
            1 => 'One (1)',
            2 => 'Two (2)',
            3 => 'Three (3)',
            4 => 'Four (4)',
            5 => 'Five (5)',
            6 => 'Six (6)',
            7 => 'Seven (7)',
            8 => 'Eight (8)',
            9 => 'Nine (9)',
            10 => 'Ten (10)',
            11 => 'Eleven (11)',
            12 => 'Twelve (12)',
            15 => 'Fifteen (15)',
            20 => 'Twenty (20)',
            default => $n.' ('.$n.')',
        };
    }

    private function formatExamDurationLabel(?int $minutes): string
    {
        if (! $minutes || $minutes <= 0) {
            return '…………';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) {
            return $h.':'.str_pad((string) $m, 2, '0', STR_PAD_LEFT).' HOURS';
        }
        if ($h > 0) {
            return $h === 1 ? '1 HOUR' : "{$h} HOURS";
        }

        return "{$minutes} MINUTES";
    }

    private function addHeaderFooter($section, ExamPaper $examPaper): void
    {
        $header = $section->addHeader();
        $header->addText(
            'NACTEVET Reg No………………………….. Examination No………………………..',
            'exam_small',
            'exam_left_single'
        );
        $header->addPreserveText(
            $this->examRunningHeaderBeforePage($examPaper).'{PAGE}',
            'exam_small',
            'exam_left_single'
        );

        $footer = $section->addFooter();
        $footer->addPreserveText(
            '-- {PAGE} of {NUMPAGES} --',
            'exam_small',
            ['alignment' => 'center']
        );
    }

    private function buildQuestionBody($section, ExamPaper $examPaper, bool $withAnswers): void
    {
        if ($examPaper->assessment_type !== 'exam') {
            $this->buildFlexibleQuestionBody($section, $examPaper, $withAnswers);

            return;
        }

        $itemsBySection = $examPaper->items->groupBy(fn ($item) => $item->section_label ?: 'Z');
        $sectionMarks = $this->sectionMarksFromItems($examPaper);
        $nextArabic = 1;

        $sectionA = $itemsBySection->get('A', collect())->values();
        if ($sectionA->isNotEmpty()) {
            $marksA = (int) round($sectionMarks['A'] ?? 0);
            $section->addText(
                'SECTION A: MULTIPLE CHOICE QUESTIONS ('.$marksA.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            $section->addText('Instructions:', 'exam_body_bold', 'exam_left_single');
            $ac = $sectionA->count();
            $section->addText('• This section consists of '.$this->examSpelledCount($ac).' questions', 'exam_body', 'exam_left_single');
            $section->addText('• Encircle the most correct answer', 'exam_body', 'exam_left_single');
            $section->addText('• One (1) mark will be awarded for each correct answer', 'exam_body', 'exam_left_single');
            $section->addTextBreak();
            foreach ($sectionA as $idx => $item) {
                $q = $item->question;
                $rn = $this->intToRomanLower($idx + 1).'.';
                $section->addText($rn.' '.$this->displayStem($q->stem), 'exam_body', 'exam_left_single');
                if (is_array($q->options)) {
                    foreach (array_values($q->options) as $option) {
                        $section->addText(($option['label'] ?? '-').'. '.($option['text'] ?? ''), 'exam_body', ['indent' => 0.5]);
                    }
                    if ($withAnswers) {
                        $letter = strtoupper(substr(trim((string) ($q->answer_key['correct'] ?? '')), 0, 1));
                        if ($letter !== '') {
                            $section->addText($letter, 'exam_body_bold', 'exam_left_single');
                        }
                    }
                }
                $section->addTextBreak();
            }
            $nextArabic = 2;
        }

        $sectionB = $itemsBySection->get('B', collect())->values();
        if ($sectionB->isNotEmpty()) {
            $marksB = (int) round($sectionMarks['B'] ?? 0);
            $section->addText(
                'SECTION B: MULTIPLE TRUE/FALSE QUESTIONS ('.$marksB.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            $section->addText('Instructions:', 'exam_body_bold', 'exam_left_single');
            $bc = $sectionB->count();
            $section->addText('• This section consists of '.$this->examSpelledCount($bc).' questions with five (5) options each', 'exam_body', 'exam_left_single');
            $section->addText('• Write the word “TRUE” and NOT letter ‘T’ for a correct statement and the word “FALSE” NOT letter ‘F’ for incorrect statement in the space provided before each option', 'exam_body', 'exam_left_single');
            $section->addText('• All responses should be in CAPITAL letters', 'exam_body', 'exam_left_single');
            $section->addText('• Half (1/2) a mark will be awarded for each correct response', 'exam_body', 'exam_left_single');
            $section->addText('• Responses with letters ‘T’ and ‘F’ will not be awarded any mark', 'exam_body', 'exam_left_single');
            $section->addText('• There will be a penalty of half (1/2) of allocated marks for responses with small letters.', 'exam_body', 'exam_left_single');
            $section->addTextBreak();
            $questionNumber = $nextArabic;
            foreach ($sectionB as $item) {
                $q = $item->question;
                $section->addText($questionNumber++.'. '.$this->displayStem($q->stem), 'exam_body', 'exam_left_single');
                if (is_array($q->options)) {
                    foreach (array_values($q->options) as $idx => $statement) {
                        $lab = $this->intToRomanLower($idx + 1).'.';
                        $section->addText($lab.' '.($statement['statement'] ?? '').' ………', 'exam_body', ['indent' => 0.5]);
                    }
                    if ($withAnswers && isset($q->answer_key['answers']) && is_array($q->answer_key['answers'])) {
                        $parts = [];
                        foreach (array_values($q->answer_key['answers']) as $ai => $a) {
                            $parts[] = $this->intToRomanLower($ai + 1).': '.($a ? 'TRUE' : 'FALSE');
                        }
                        $section->addText('Answer Guide: '.implode('; ', $parts), 'exam_body', 'exam_left_single');
                    }
                }
                $section->addTextBreak();
            }
            $nextArabic = $questionNumber;
        }

        $sectionC = $itemsBySection->get('C', collect())->values();
        if ($sectionC->isNotEmpty()) {
            $marksC = (int) round($sectionMarks['C'] ?? 0);
            $section->addText(
                'SECTION C: MATCHING ITEMS ('.$marksC.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            $section->addText('Instructions', 'exam_body_bold', 'exam_left_single');
            $cc = $sectionC->count();
            $section->addText('• This section consists of '.$this->examSpelledCount($cc).' questions of matching with five (5) options each', 'exam_body', 'exam_left_single');
            $section->addText('• Match the items from column B with those in column A by writing the letter of correct response in the space provided on each option. USE CAPITAL LETTERS', 'exam_body', 'exam_left_single');
            $section->addText('• Each correct response is awarded one (1) mark', 'exam_body', 'exam_left_single');
            $section->addTextBreak();
            $cNum = $nextArabic;
            foreach ($sectionC as $idx => $item) {
                $q = $item->question;
                $prefix = $idx === 0 ? $cNum.'. ' : chr(ord('A') + $idx).': ';
                $section->addText($prefix.$this->displayStem($q->stem), 'exam_body', 'exam_left_single');
                if ($q->type === 'matching' && is_array($q->options) && isset($q->options['pairs'])) {
                    $pairs = array_slice(array_values($q->options['pairs']), 0, 5);
                    $columnB = $this->matchingColumnBChoicesEight($pairs);

                    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                    $table->addRow();
                    $table->addCell(4500)->addText('COLUMN A: TERMS');
                    $table->addCell(4500)->addText('COLUMN B: ALTERNATIVES (A–H)');
                    for ($r = 0; $r < 8; $r++) {
                        $table->addRow();
                        $leftCell = $r < count($pairs)
                            ? $this->intToRomanLower($r + 1).'. '.trim((string) ($pairs[$r]['left'] ?? ''))
                            : '';
                        $table->addCell(4500)->addText($leftCell);
                        $bText = trim((string) ($columnB[$r] ?? ''));
                        $table->addCell(4500)->addText(chr(65 + $r).'. '.($bText !== '' ? $bText : '…………'));
                    }

                    $answerTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                    $answerTable->addRow();
                    $answerTable->addCell(2200)->addText('COLUMN A');
                    foreach (['i', 'ii', 'iii', 'iv', 'v'] as $h) {
                        $answerTable->addCell(1200)->addText($h);
                    }
                    $answerTable->addRow();
                    $answerTable->addCell(2200)->addText('COLUMN B');
                    foreach (['i', 'ii', 'iii', 'iv', 'v'] as $_) {
                        $answerTable->addCell(1200)->addText('');
                    }

                    if ($withAnswers) {
                        $guide = $q->answer_key['pairs'] ?? [];
                        $section->addText('Answer Guide: '.implode(', ', is_array($guide) ? array_slice($guide, 0, 5) : []), 'exam_body', 'exam_left_single');
                    }
                }
                $section->addTextBreak();
            }
            $nextArabic = $cNum + 1;
        }

        $sectionD = $itemsBySection->get('D', collect())->values();
        if ($sectionD->isNotEmpty()) {
            $marksD = (int) round($sectionMarks['D'] ?? 0);
            $section->addText(
                'SECTION D: SHORT ANSWER QUESTIONS ('.$marksD.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            $section->addText('Instructions:', 'exam_body_bold', 'exam_left_single');
            $dc = $sectionD->count();
            $section->addText('• This section consists of '.$this->examSpelledCount($dc).' questions.', 'exam_body', 'exam_left_single');
            $section->addText('• Write your answers in the space provided on each question.', 'exam_body', 'exam_left_single');
            $section->addText('• Write a readable handwrite; DIRTY WORK IS NOT ALLOWED', 'exam_body', 'exam_left_single');
            $section->addTextBreak();
            $questionNumber = $nextArabic;
            foreach ($sectionD as $item) {
                $q = $item->question;
                $section->addText($questionNumber++.'. '.$this->displayStem($q->stem), 'exam_body', 'exam_left_single');
                foreach (['i', 'ii', 'iii', 'iv', 'v'] as $sp) {
                    $section->addText($sp.'. ………………………………………………………………………………………………………', 'exam_body', ['indent' => 0.35]);
                }
                if ($withAnswers) {
                    $sample = $q->answer_key['sample'] ?? null;
                    if ($sample) {
                        $section->addText('Answer Guide: '.$sample, 'exam_body', 'exam_left_single');
                    }
                }
                $section->addTextBreak();
            }
            $nextArabic = $questionNumber;
        }

        $sectionE = $itemsBySection->get('E', collect())->values();
        if ($sectionE->isNotEmpty()) {
            $marksE = (int) round($sectionMarks['E'] ?? 0);
            $section->addText(
                'SECTION E: ESSAY QUESTIONS ('.$marksE.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            $section->addText('Instructions', 'exam_body_bold', 'exam_left_single');
            $ec = $sectionE->count();
            $section->addText('• This section consists of '.$this->examSpelledCount($ec).' questions which are supposed to be answered in a narrative way.', 'exam_body', 'exam_left_single');
            $section->addText('• Write your answer on the empty pages of this question paper; each question should start on a new page.', 'exam_body', 'exam_left_single');
            $section->addText('• There will be a penalty of three (3) marks from score attained in this section if question(s) is/are not answered in essay form', 'exam_body', 'exam_left_single');
            $section->addTextBreak();
            $questionNumber = $nextArabic;
            foreach ($sectionE as $item) {
                $q = $item->question;
                $section->addText($questionNumber++.'. '.$this->displayStem($q->stem), 'exam_body', 'exam_left_single');
                if ($withAnswers) {
                    $sample = $q->answer_key['sample_outline'] ?? null;
                    if (is_array($sample)) {
                        $sample = implode(' | ', $sample);
                    }
                    if ($sample) {
                        $section->addText('Answer Guide: '.$sample, 'exam_body', 'exam_left_single');
                    }
                }
                $section->addTextBreak();
            }
        }

    }

    /**
     * Simple renderer for quiz/assignment papers: grouped by question type,
     * no fixed section letters and no NACTVET-specific instruction wording.
     */
    private function buildFlexibleQuestionBody($section, ExamPaper $examPaper, bool $withAnswers): void
    {
        $typeLabels = [
            'mcq' => 'Multiple Choice Questions',
            'multi_true_false' => 'True/False Questions',
            'matching' => 'Matching Questions',
            'short_answer' => 'Short Answer Questions',
            'essay' => 'Essay Questions',
        ];

        $itemsByType = $examPaper->items->groupBy(fn ($item) => $item->question->type ?? 'mcq');
        $questionNumber = 1;

        foreach ($typeLabels as $type => $heading) {
            $items = $itemsByType->get($type, collect())->values();
            if ($items->isEmpty()) {
                continue;
            }

            $groupMarks = (int) round($items->sum('marks'));
            $section->addText(
                strtoupper($heading).' ('.$groupMarks.' MARKS)',
                'exam_body_bold',
                ['spaceBefore' => 200, 'spaceAfter' => 120, 'alignment' => 'left']
            );
            if ($instruction = $items->first()->section_instruction) {
                $section->addText($instruction, 'exam_body', 'exam_left_single');
            }
            $section->addTextBreak();

            foreach ($items as $item) {
                $q = $item->question;
                $section->addText($questionNumber++.'. '.$this->displayStem($q->stem).' ('.(int) round((float) $item->marks).' marks)', 'exam_body', 'exam_left_single');

                match ($type) {
                    'mcq' => $this->renderFlexibleMcq($section, $q, $withAnswers),
                    'multi_true_false' => $this->renderFlexibleMultiTrueFalse($section, $q, $withAnswers),
                    'matching' => $this->renderFlexibleMatching($section, $q, $withAnswers),
                    'short_answer' => $this->renderFlexibleShortAnswer($section, $q, $withAnswers),
                    default => $this->renderFlexibleEssay($section, $q, $withAnswers),
                };

                $section->addTextBreak();
            }
        }
    }

    private function renderFlexibleMcq($section, QuestionItem $q, bool $withAnswers): void
    {
        if (! is_array($q->options)) {
            return;
        }
        foreach (array_values($q->options) as $option) {
            $section->addText(($option['label'] ?? '-').'. '.($option['text'] ?? ''), 'exam_body', ['indent' => 0.5]);
        }
        if ($withAnswers) {
            $letter = strtoupper(substr(trim((string) ($q->answer_key['correct'] ?? '')), 0, 1));
            if ($letter !== '') {
                $section->addText('Answer: '.$letter, 'exam_body_bold', 'exam_left_single');
            }
        }
    }

    private function renderFlexibleMultiTrueFalse($section, QuestionItem $q, bool $withAnswers): void
    {
        if (! is_array($q->options)) {
            return;
        }
        foreach (array_values($q->options) as $idx => $statement) {
            $lab = $this->intToRomanLower($idx + 1).'.';
            $section->addText($lab.' '.($statement['statement'] ?? '').' ………', 'exam_body', ['indent' => 0.5]);
        }
        if ($withAnswers && isset($q->answer_key['answers']) && is_array($q->answer_key['answers'])) {
            $parts = [];
            foreach (array_values($q->answer_key['answers']) as $ai => $a) {
                $parts[] = $this->intToRomanLower($ai + 1).': '.($a ? 'TRUE' : 'FALSE');
            }
            $section->addText('Answer: '.implode('; ', $parts), 'exam_body', 'exam_left_single');
        }
    }

    private function renderFlexibleMatching($section, QuestionItem $q, bool $withAnswers): void
    {
        if (! is_array($q->options) || ! isset($q->options['pairs'])) {
            return;
        }
        $pairs = array_values($q->options['pairs']);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        $table->addRow();
        $table->addCell(4500)->addText('COLUMN A');
        $table->addCell(4500)->addText('COLUMN B');
        foreach ($pairs as $pair) {
            $table->addRow();
            $table->addCell(4500)->addText(trim((string) ($pair['left'] ?? '')));
            $table->addCell(4500)->addText($withAnswers ? trim((string) ($pair['right'] ?? '')) : '…………');
        }
    }

    private function renderFlexibleShortAnswer($section, QuestionItem $q, bool $withAnswers): void
    {
        foreach (['i', 'ii', 'iii', 'iv', 'v'] as $sp) {
            $section->addText($sp.'. ………………………………………………………………………………………………………', 'exam_body', ['indent' => 0.35]);
        }
        if ($withAnswers && ($sample = $q->answer_key['sample'] ?? null)) {
            $section->addText('Answer Guide: '.$sample, 'exam_body', 'exam_left_single');
        }
    }

    private function renderFlexibleEssay($section, QuestionItem $q, bool $withAnswers): void
    {
        if (! $withAnswers) {
            return;
        }
        if (! empty($q->rubric['parts']) && is_array($q->rubric['parts'])) {
            $parts = array_map(fn ($p) => ($p['label'] ?? '').' ('.($p['marks'] ?? 0).' marks)', $q->rubric['parts']);
            $section->addText('Marking guide: '.implode(', ', $parts), 'exam_body', 'exam_left_single');
        } elseif (! empty($q->answer_key['sample_outline'])) {
            $sample = $q->answer_key['sample_outline'];
            $section->addText('Answer Guide: '.(is_array($sample) ? implode(' | ', $sample) : $sample), 'exam_body', 'exam_left_single');
        }
    }

    private function intToRomanLower(int $num): string
    {
        $map = [
            10 => 'x', 9 => 'ix', 5 => 'v', 4 => 'iv', 1 => 'i',
        ];
        $vals = [10, 9, 5, 4, 1];
        $result = '';
        foreach ($vals as $v) {
            while ($num >= $v) {
                $result .= $map[$v];
                $num -= $v;
            }
        }

        return $result;
    }

    /**
     * Column B: A–H as multiple-choice-style lines. A–E = paired “right” answers for rows (i)–(v);
     * F–H = extra lines from the same pairs’ left/right texts (no fabricated distractors). Remaining slots use …………
     *
     * @param  array<int, array<string, mixed>>  $pairs
     * @return array<int, string>
     */
    private function matchingColumnBChoicesEight(array $pairs): array
    {
        $pairs = array_values(array_slice($pairs, 0, 5));
        $choices = array_fill(0, 8, '…………');
        foreach ($pairs as $i => $p) {
            if ($i < 5) {
                $r = trim((string) ($p['right'] ?? ''));
                $choices[$i] = $r !== '' ? $r : '…………';
            }
        }
        $pool = [];
        foreach ($pairs as $p) {
            foreach (['left', 'right'] as $side) {
                $t = trim((string) ($p[$side] ?? ''));
                if ($t !== '') {
                    $pool[] = $t;
                }
            }
        }
        $pool = array_values(array_unique($pool));
        $k = 5;
        foreach ($pool as $t) {
            if ($k >= 8) {
                break;
            }
            if (! in_array($t, array_slice($choices, 0, $k), true)) {
                $choices[$k++] = $t;
            }
        }
        while ($k < 8) {
            $choices[$k++] = '…………';
        }

        return $choices;
    }

    private function displayStem(string $stem): string
    {
        return (string) preg_replace('/^\(\d+\)\s*/', '', trim($stem));
    }

    private function sectionMarksFromItems(ExamPaper $examPaper): array
    {
        $marks = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
        foreach ($examPaper->items as $item) {
            if (isset($marks[$item->section_label])) {
                $marks[$item->section_label] += (float) $item->marks;
            }
        }

        return $marks;
    }

    private function configureDocumentStyles(PhpWord $phpWord): void
    {
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['alignment' => 'center', 'spaceAfter' => 120]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => 'left', 'spaceBefore' => 120, 'spaceAfter' => 80]);

        $phpWord->addFontStyle('exam_heading', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('exam_body', ['name' => 'Times New Roman', 'size' => 12]);
        $phpWord->addFontStyle('exam_body_bold', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('exam_small', ['name' => 'Times New Roman', 'size' => 10]);

        $phpWord->addParagraphStyle('exam_center_single', ['alignment' => 'center', 'spaceAfter' => 0, 'lineHeight' => 1.0]);
        $phpWord->addParagraphStyle('exam_left_single', ['alignment' => 'left', 'spaceAfter' => 80, 'lineHeight' => 1.0]);
    }

    private function sectionLayout(): array
    {
        return [
            'marginTop' => 1134,      // 2.0 cm
            'marginBottom' => 1134,   // 2.0 cm
            'marginLeft' => 1417,     // 2.5 cm
            'marginRight' => 1134,    // 2.0 cm
            'headerHeight' => 567,    // 1.0 cm
            'footerHeight' => 567,    // 1.0 cm
        ];
    }

    private function extractGenerationText(QuestionMaterial $material): string
    {
        $text = $this->sanitizeUtf8($material->text_content ?? '');
        if ($text !== '') {
            return $text;
        }

        if (! $material->file_path || ! Storage::disk('public')->exists($material->file_path)) {
            return '';
        }

        $extension = strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION));
        $absolutePath = Storage::disk('public')->path($material->file_path);
        if (! is_file($absolutePath)) {
            return '';
        }

        $extracted = $this->extractTextFromStoredFile($absolutePath, $extension);

        return $this->sanitizeUtf8($extracted);
    }

    private function extractTextFromStoredFile(string $absolutePath, string $extension): string
    {
        return match ($extension) {
            'txt', 'md', 'csv' => (string) @file_get_contents($absolutePath),
            'docx' => $this->extractDocxText($absolutePath),
            'pdf' => PdfTextExtractor::extract($absolutePath),
            'doc' => $this->extractDocText($absolutePath),
            default => '',
        };
    }

    /**
     * Extract text from DOCX using PhpWord bundled Zip fallback (works without php_zip extension).
     */
    private function extractDocxText(string $absolutePath): string
    {
        $zip = new PhpWordZipArchive;
        if (! $zip->open($absolutePath)) {
            return '';
        }

        $xml = '';
        $index = $zip->locateName('word/document.xml');
        if ($index !== false) {
            $xml = (string) $zip->getFromIndex((int) $index);
        }
        $zip->close();

        if ($xml === '') {
            return '';
        }

        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return (string) $text;
    }

    /**
     * Basic DOC binary extraction (best effort).
     */
    private function extractDocText(string $absolutePath): string
    {
        $raw = (string) @file_get_contents($absolutePath);
        if ($raw === '') {
            return '';
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $raw) ?? '';
        $text = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $text) ?? '';

        return (string) $text;
    }

    private function materialExtractionFailedMessage(): string
    {
        return 'No valid text could be extracted from this material. For PDFs, use a text-based file (not a scanned image), install Poppler and ensure pdftotext is on your PATH (or set PDFTOTEXT_PATH), or upload TXT/DOCX and paste plain text in the material form, then try again.';
    }

    private function sanitizeUtf8(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        // Remove control characters except whitespace we want to keep.
        $value = preg_replace('/[^\P{C}\n\r\t]+/u', '', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return Str::limit(trim($value), 12000, '');
    }
}

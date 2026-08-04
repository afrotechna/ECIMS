<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasNactvetSectionMap;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionItemController extends Controller
{
    use HasNactvetSectionMap;

    public function create(QuestionBank $questionBank): View
    {
        return view('assessment-studio.questions.form', [
            'questionBank' => $questionBank,
            'questionItem' => null,
            'typeLabels' => $this->questionTypeLabels(),
        ]);
    }

    public function store(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $data = $this->validated($request);

        $questionBank->questions()->create(array_merge($data, [
            'course_id' => $questionBank->course_id,
            'question_material_id' => null,
            'is_ai_generated' => false,
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('assessment-studio.show', $questionBank)->with('success', 'Question added.');
    }

    public function edit(QuestionBank $questionBank, QuestionItem $questionItem): View
    {
        abort_unless($questionItem->question_bank_id === $questionBank->id, 404);

        return view('assessment-studio.questions.form', [
            'questionBank' => $questionBank,
            'questionItem' => $questionItem,
            'typeLabels' => $this->questionTypeLabels(),
        ]);
    }

    public function update(Request $request, QuestionBank $questionBank, QuestionItem $questionItem): RedirectResponse
    {
        abort_unless($questionItem->question_bank_id === $questionBank->id, 404);

        $questionItem->update($this->validated($request));

        return redirect()->route('assessment-studio.show', $questionBank)->with('success', 'Question updated.');
    }

    public function destroy(Request $request, QuestionBank $questionBank, QuestionItem $questionItem): RedirectResponse
    {
        abort_unless($questionItem->question_bank_id === $questionBank->id, 404);

        if ($questionItem->examItems()->exists()) {
            return redirect()->route('assessment-studio.show', $questionBank)
                ->with('error', 'This question is used on a saved assessment. Delete that assessment first if you want to remove the question.');
        }

        $questionItem->delete();

        return redirect()->route('assessment-studio.show', $questionBank)->with('success', 'Question deleted.');
    }

    /**
     * Validate the request and shape it into the stored options/answer_key/rubric arrays,
     * matching what QuestionBankController::buildQuestionBody() reads back for export.
     */
    private function validated(Request $request): array
    {
        $base = $request->validate([
            'type' => ['required', 'in:mcq,multi_true_false,matching,short_answer,essay'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'stem' => ['required', 'string'],
            'marks' => ['required', 'numeric', 'min:0.5', 'max:100'],
        ]);

        return match ($base['type']) {
            'mcq' => $this->validatedMcq($request, $base),
            'multi_true_false' => $this->validatedMultiTrueFalse($request, $base),
            'matching' => $this->validatedMatching($request, $base),
            'short_answer' => $this->validatedShortAnswer($request, $base),
            'essay' => $this->validatedEssay($request, $base),
        };
    }

    private function validatedMcq(Request $request, array $base): array
    {
        $validated = $request->validate([
            'options' => ['required', 'array', 'size:5'],
            'options.*.text' => ['required', 'string'],
            'correct_option' => ['required', 'integer', 'min:0', 'max:4'],
        ]);

        $labels = ['A', 'B', 'C', 'D', 'E'];
        $options = [];
        foreach (array_values($validated['options']) as $i => $option) {
            $options[] = [
                'label' => $labels[$i],
                'text' => $option['text'],
                'is_correct' => (int) $validated['correct_option'] === $i,
            ];
        }

        return array_merge($base, [
            'options' => $options,
            'answer_key' => ['correct' => $labels[(int) $validated['correct_option']]],
            'rubric' => null,
        ]);
    }

    private function validatedMultiTrueFalse(Request $request, array $base): array
    {
        $validated = $request->validate([
            'statements' => ['required', 'array', 'size:5'],
            'statements.*.text' => ['required', 'string'],
            'statements.*.answer' => ['required', 'in:0,1'],
        ]);

        $options = [];
        $answers = [];
        foreach (array_values($validated['statements']) as $statement) {
            $isTrue = (bool) (int) $statement['answer'];
            $options[] = ['statement' => $statement['text'], 'answer' => $isTrue];
            $answers[] = $isTrue;
        }

        return array_merge($base, [
            'options' => $options,
            'answer_key' => ['answers' => $answers],
            'rubric' => null,
        ]);
    }

    private function validatedMatching(Request $request, array $base): array
    {
        $validated = $request->validate([
            'pairs' => ['required', 'array', 'min:1', 'max:5'],
            'pairs.*.left' => ['required', 'string'],
            'pairs.*.right' => ['required', 'string'],
        ]);

        $pairs = [];
        $answerPairs = [];
        foreach (array_values($validated['pairs']) as $i => $pair) {
            $pairs[] = ['left' => $pair['left'], 'right' => $pair['right']];
            $answerPairs[] = ($i + 1).'-'.chr(65 + $i);
        }

        return array_merge($base, [
            'options' => ['pairs' => $pairs],
            'answer_key' => ['pairs' => $answerPairs],
            'rubric' => null,
        ]);
    }

    private function validatedShortAnswer(Request $request, array $base): array
    {
        $validated = $request->validate([
            'points' => ['required', 'array', 'min:1', 'max:10'],
            'points.*' => ['required', 'string'],
        ]);

        $points = array_values(array_filter(array_map('trim', $validated['points']), fn ($p) => $p !== ''));

        return array_merge($base, [
            'options' => null,
            'answer_key' => ['sample' => implode('; ', $points)],
            'rubric' => ['criteria' => ['Accuracy', 'Clarity', 'Use of appropriate terms']],
        ]);
    }

    private function validatedEssay(Request $request, array $base): array
    {
        $validated = $request->validate([
            'rubric_parts' => ['required', 'array', 'min:1', 'max:10'],
            'rubric_parts.*.label' => ['required', 'string'],
            'rubric_parts.*.marks' => ['required', 'numeric', 'min:0'],
        ]);

        $parts = array_values($validated['rubric_parts']);

        return array_merge($base, [
            'options' => null,
            'answer_key' => ['sample_outline' => array_column($parts, 'label')],
            'rubric' => ['parts' => $parts],
        ]);
    }
}

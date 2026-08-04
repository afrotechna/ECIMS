<?php

namespace App\Services\QuestionGeneration;

use App\Services\QuestionGeneration\Support\TextFragmentExtractor;

/**
 * Structured-draft generator: builds questions from real fragments of the
 * uploaded material (not a real language model). Draws on a rotating pool of
 * distinct sentences/clauses so items in one batch don't repeat identical
 * content, and MCQ/matching content is built from the source text itself
 * rather than fixed boilerplate. Still a starting draft for staff to edit,
 * not exam-ready content on its own.
 */
class TemplateQuestionGenerator implements QuestionGeneratorContract
{
    public function __construct(private readonly TextFragmentExtractor $extractor = new TextFragmentExtractor)
    {
    }

    public function generate(string $type, string $sourceText, int $count = 5, string $difficulty = 'medium'): array
    {
        $cleanText = $this->normalize($sourceText);
        if ($cleanText === '') {
            return [];
        }

        $sentences = $this->extractor->sentences($cleanText);
        if ($sentences === []) {
            $sentences = [$cleanText];
        }

        // Fragments consumed per item varies by type; over-provision so a full
        // batch can draw fresh material before the pool has to repeat.
        $fragmentsPerItem = match ($type) {
            'multi_true_false', 'matching' => 5,
            default => 3,
        };
        $pool = $this->extractor->distinctFragments($sentences, max($count * $fragmentsPerItem, 10));
        $cursor = 0;
        $next = function () use (&$cursor, $pool) {
            $fragment = $pool[$cursor % count($pool)];
            $cursor++;

            return $this->normalize($fragment, 260);
        };

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = $this->buildByType($type, $next, $difficulty, $i + 1);
        }

        return $items;
    }

    private function buildByType(string $type, callable $next, string $difficulty, int $num): array
    {
        return match ($type) {
            'mcq' => $this->buildMcq($next, $difficulty, $num),
            'multi_true_false' => $this->buildMultiTrueFalse($next, $difficulty, $num),
            'matching' => $this->buildMatching($next, $difficulty, $num),
            'short_answer' => $this->buildShortAnswer($next, $difficulty, $num),
            default => $this->buildEssay($next, $difficulty, $num),
        };
    }

    private function buildMcq(callable $next, string $difficulty, int $num): array
    {
        $context = $next();
        $correctTerm = $this->extractor->keyTerm($context);

        $distractorTerms = [];
        for ($i = 0; $i < 4; $i++) {
            $term = $this->extractor->keyTerm($next());
            if ($term !== $correctTerm && ! in_array($term, $distractorTerms, true)) {
                $distractorTerms[] = $term;
            }
        }
        while (count($distractorTerms) < 4) {
            $distractorTerms[] = $this->extractor->keyTerm($next());
        }

        $labels = ['A', 'B', 'C', 'D', 'E'];
        $texts = array_merge([$context], array_map(
            fn ($term) => "A statement built around \"{$term}\" that does not match the passage above.",
            $distractorTerms
        ));
        $correctIndex = $num % 5; // rotate which slot holds the correct answer across items
        $shuffled = [];
        $ci = 0;
        foreach ($labels as $i => $label) {
            $shuffled[$label] = $i === $correctIndex ? $texts[0] : $texts[1 + ($ci++)];
        }

        $options = [];
        foreach ($labels as $label) {
            $options[] = ['label' => $label, 'text' => $shuffled[$label], 'is_correct' => $label === $labels[$correctIndex]];
        }

        return [
            'type' => 'mcq',
            'difficulty' => $difficulty,
            'stem' => "($num) Based on the material provided, which statement is correct?",
            'options' => $options,
            'answer_key' => ['correct' => $labels[$correctIndex]],
            'rubric' => null,
        ];
    }

    private function buildMultiTrueFalse(callable $next, string $difficulty, int $num): array
    {
        $options = [];
        $answers = [];
        for ($i = 0; $i < 5; $i++) {
            $fragment = $next();
            $isTrue = $i % 2 === 0; // alternate true/false, grounded in real fragments either way
            $statement = $isTrue ? $fragment : $this->negate($fragment);
            $options[] = ['statement' => $statement, 'answer' => $isTrue];
            $answers[] = $isTrue;
        }

        return [
            'type' => 'multi_true_false',
            'difficulty' => $difficulty,
            'stem' => "($num) Read the following statements drawn from the material and mark each True or False:",
            'options' => $options,
            'answer_key' => ['answers' => $answers],
            'rubric' => null,
        ];
    }

    private function buildMatching(callable $next, string $difficulty, int $num): array
    {
        $pairs = [];
        for ($i = 0; $i < 5; $i++) {
            $fragment = $next();
            $pairs[] = ['left' => $this->extractor->keyTerm($fragment), 'right' => $fragment];
        }

        $answerPairs = [];
        foreach (array_keys($pairs) as $i) {
            $answerPairs[] = ($i + 1).'-'.chr(65 + $i);
        }

        return [
            'type' => 'matching',
            'difficulty' => $difficulty,
            'stem' => "($num) Match each term in Column A with the description in Column B that it belongs to.",
            'options' => ['pairs' => $pairs],
            'answer_key' => ['pairs' => $answerPairs],
            'rubric' => null,
        ];
    }

    private function buildShortAnswer(callable $next, string $difficulty, int $num): array
    {
        $context = $next();
        $points = [];
        for ($i = 0; $i < 5; $i++) {
            $points[] = $this->extractor->keyTerm($next());
        }

        return [
            'type' => 'short_answer',
            'difficulty' => $difficulty,
            'stem' => "($num) Based on this content: \"{$context}\". Mention FIVE (5) key points.",
            'options' => null,
            'answer_key' => ['sample' => implode('; ', $points)],
            'rubric' => ['criteria' => ['Accuracy', 'Clarity', 'Use of appropriate terms']],
        ];
    }

    private function buildEssay(callable $next, string $difficulty, int $num): array
    {
        $context = $next();

        return [
            'type' => 'essay',
            'difficulty' => $difficulty,
            'stem' => "($num) Discuss the following in detail, with examples: \"{$context}\"",
            'options' => null,
            'answer_key' => ['sample_outline' => ['Introduction', 'Main argument', 'Evidence/examples', 'Conclusion']],
            'rubric' => ['criteria' => ['Depth of analysis', 'Organization', 'Use of evidence', 'Language quality']],
        ];
    }

    /** Cheap negation for a False statement: only reliable for simple sentences, good enough for a draft. */
    private function negate(string $sentence): string
    {
        $replacements = [
            ' is ' => ' is not ',
            ' are ' => ' are not ',
            ' was ' => ' was not ',
            ' were ' => ' were not ',
            ' can ' => ' cannot ',
            ' will ' => ' will not ',
            ' has ' => ' does not have ',
            ' have ' => ' do not have ',
        ];
        foreach ($replacements as $find => $replace) {
            if (str_contains($sentence, $find)) {
                return str_replace($find, $replace, $sentence);
            }
        }

        return 'It is NOT true that: '.$sentence;
    }

    private function normalize(string $text, int $maxLen = 4000): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        $text = preg_replace('/[^\P{C}\n\r\t]+/u', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim(mb_substr($text, 0, $maxLen));
    }
}

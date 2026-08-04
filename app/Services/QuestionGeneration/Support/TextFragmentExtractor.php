<?php

namespace App\Services\QuestionGeneration\Support;

/**
 * Splits source material into distinct, reusable text fragments so generated
 * questions in the same batch don't repeat identical content. This is a
 * heuristic text splitter, not an NLP/AI tool — it extracts real fragments
 * from the source instead of fabricating boilerplate.
 */
class TextFragmentExtractor
{
    private static array $stopWords = [
        'the', 'and', 'that', 'with', 'from', 'this', 'these', 'those', 'which', 'their',
        'have', 'has', 'been', 'were', 'will', 'shall', 'when', 'where', 'while', 'into',
        'onto', 'also', 'such', 'more', 'most', 'some', 'each', 'other', 'than', 'then',
        'because', 'about', 'after', 'before', 'during', 'between', 'both', 'either',
    ];

    /** Split cleaned text into sentence-like fragments, longest-first is NOT applied (keeps source order). */
    public function sentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($text)) ?: [];

        return array_values(array_filter($sentences, fn ($s) => mb_strlen(trim($s)) > 30));
    }

    /** Split one sentence into smaller clauses (comma/semicolon boundaries) for when sentences run out. */
    public function clauses(string $sentence): array
    {
        $clauses = preg_split('/[,;]\s+/', trim($sentence)) ?: [];

        return array_values(array_filter($clauses, fn ($c) => mb_strlen(trim($c)) > 20));
    }

    /**
     * Return $count fragments from the pool, reusing each sentence at most once
     * before falling back to clause-level splitting, so items within one
     * generation batch quote different parts of the source material.
     *
     * @return array<int, string>
     */
    public function distinctFragments(array $sentences, int $count): array
    {
        if ($sentences === []) {
            return [];
        }

        $fragments = [];
        $usedSentences = [];

        foreach ($sentences as $sentence) {
            if (count($fragments) >= $count) {
                break;
            }
            $fragments[] = trim($sentence);
            $usedSentences[] = $sentence;
        }

        if (count($fragments) >= $count) {
            return $fragments;
        }

        // Ran out of whole sentences: fall back to clauses within already-used sentences.
        foreach ($usedSentences as $sentence) {
            foreach ($this->clauses($sentence) as $clause) {
                if (count($fragments) >= $count) {
                    break 2;
                }
                if (! in_array($clause, $fragments, true)) {
                    $fragments[] = trim($clause);
                }
            }
        }

        // Still short: combine adjacent sentence pairs rather than repeat one verbatim.
        $i = 0;
        while (count($fragments) < $count && $sentences !== []) {
            $a = $sentences[$i % count($sentences)];
            $b = $sentences[($i + 1) % count($sentences)];
            $combined = trim($a).' '.trim($b);
            if (! in_array($combined, $fragments, true)) {
                $fragments[] = $combined;
            }
            $i++;
            if ($i > count($sentences) * 2) {
                break; // pool genuinely exhausted; avoid an infinite loop
            }
        }

        return $fragments;
    }

    /**
     * Heuristic "key term" for a sentence: the longest capitalized word, or
     * otherwise the longest non-stopword — used to build matching pairs and
     * MCQ distractors that reference real material instead of filler text.
     */
    public function keyTerm(string $sentence): string
    {
        $words = preg_split('/[^\p{L}\p{N}\'-]+/u', $sentence) ?: [];
        $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) > 3));

        $capitalized = array_values(array_filter($words, fn ($w) => preg_match('/^\p{Lu}/u', $w) === 1));
        $candidates = $capitalized !== [] ? $capitalized : array_values(array_filter(
            $words,
            fn ($w) => ! in_array(mb_strtolower($w), self::$stopWords, true)
        ));

        if ($candidates === []) {
            return trim(mb_substr($sentence, 0, 40));
        }

        usort($candidates, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $candidates[0];
    }
}

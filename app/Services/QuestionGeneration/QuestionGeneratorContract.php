<?php

namespace App\Services\QuestionGeneration;

interface QuestionGeneratorContract
{
    /**
     * Generate $count draft questions of $type from $sourceText.
     * Each returned item has the same shape QuestionItem expects to store:
     * type, difficulty, stem, options, answer_key, rubric.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generate(string $type, string $sourceText, int $count = 5, string $difficulty = 'medium'): array;
}

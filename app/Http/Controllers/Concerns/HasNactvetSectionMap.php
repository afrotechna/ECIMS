<?php

namespace App\Http\Controllers\Concerns;

trait HasNactvetSectionMap
{
    /**
     * The five question types allowed on any assessment, and their letter used
     * only for the fixed-structure NACTVET exam (sections A-E).
     */
    protected function sectionTypeMap(): array
    {
        return [
            'A' => 'mcq',
            'B' => 'multi_true_false',
            'C' => 'matching',
            'D' => 'short_answer',
            'E' => 'essay',
        ];
    }

    /** Fixed section item counts for the official NACTVET exam structure (exam assessment type only). */
    protected function requiredCountsBySection(): array
    {
        return ['A' => 20, 'B' => 4, 'C' => 2, 'D' => 6, 'E' => 2];
    }

    /** Same counts keyed by question type instead of section letter. */
    protected function requiredCountsByType(): array
    {
        $counts = [];
        foreach ($this->requiredCountsBySection() as $section => $count) {
            $counts[$this->sectionTypeMap()[$section]] = $count;
        }

        return $counts;
    }

    /** Human labels for each question type, used in question lists/forms. */
    protected function questionTypeLabels(): array
    {
        return [
            'mcq' => 'Multiple Choice',
            'multi_true_false' => 'Multiple True/False',
            'matching' => 'Matching',
            'short_answer' => 'Short Answer',
            'essay' => 'Guided Essay',
        ];
    }
}

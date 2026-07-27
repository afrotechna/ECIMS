<?php

namespace App\Services;

class QuestionGenerationService
{
    public function generate(string $type, string $sourceText, int $count = 5, string $difficulty = 'medium'): array
    {
        $cleanText = $this->normalize($sourceText);
        if ($cleanText === '') {
            return [];
        }

        $sentences = preg_split('/(?<=[\.\!\?])\s+/', $cleanText) ?: [];
        $seed = array_values(array_filter($sentences, fn ($line) => mb_strlen($line) > 30));
        if ($seed === []) {
            $seed = [$cleanText];
        }

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $context = $this->normalize($seed[$i % count($seed)], 260);
            $items[] = $this->buildByType($type, $context, $difficulty, $i + 1);
        }

        return $items;
    }

    private function buildByType(string $type, string $context, string $difficulty, int $num): array
    {
        return match ($type) {
            'mcq' => [
                'type' => 'mcq',
                'difficulty' => $difficulty,
                'stem' => "($num) Based on this content: \"$context\". Which statement is most correct?",
                'options' => $this->buildMcqOptions($num),
                'answer_key' => ['correct' => $this->mcqCorrectLabel($num)],
                'rubric' => null,
            ],
            'multi_true_false' => [
                'type' => 'multi_true_false',
                'difficulty' => $difficulty,
                'stem' => "($num) Read the following and mark each statement as True or False: \"$context\"",
                'options' => [
                    ['statement' => 'The statement is consistent with the main point of the passage.', 'answer' => true],
                    ['statement' => 'The statement contradicts the central concept.', 'answer' => false],
                    ['statement' => 'The statement extends the idea using valid logic.', 'answer' => true],
                    ['statement' => 'The statement introduces a wrong definition of terms.', 'answer' => false],
                    ['statement' => 'The statement aligns with accepted practical examples.', 'answer' => true],
                ],
                'answer_key' => ['answers' => [true, false, true, false, true]],
                'rubric' => null,
            ],
            'matching' => [
                'type' => 'matching',
                'difficulty' => $difficulty,
                'stem' => "($num) Match each concept in Column A with the correct description in Column B (Part A: 1-5, Part B: 6-10).",
                'options' => [
                    'pairs' => [
                        ['left' => 'Core idea from the text', 'right' => 'Main concept learners should master'],
                        ['left' => 'Supporting detail', 'right' => 'Evidence that explains the main concept'],
                        ['left' => 'Common error', 'right' => 'Frequent misunderstanding to avoid'],
                        ['left' => 'Application case', 'right' => 'Practical use of the concept'],
                        ['left' => 'Definition', 'right' => 'Precise meaning of the key term'],
                        ['left' => 'Process step', 'right' => 'Ordered action in the procedure'],
                        ['left' => 'Assessment indicator', 'right' => 'What should be measured'],
                        ['left' => 'Quality criterion', 'right' => 'Standard used for evaluation'],
                        ['left' => 'Resource requirement', 'right' => 'Input needed to complete the task'],
                        ['left' => 'Expected outcome', 'right' => 'Result after applying the concept'],
                    ],
                ],
                'answer_key' => ['pairs' => ['1-A', '2-B', '3-C', '4-D', '5-E', '6-F', '7-G', '8-H', '9-I', '10-J']],
                'rubric' => null,
            ],
            'short_answer' => [
                'type' => 'short_answer',
                'difficulty' => $difficulty,
                'stem' => "($num) Mention FIVE (5) key points related to this statement: \"$context\"",
                'options' => null,
                'answer_key' => ['sample' => 'Point 1; Point 2; Point 3; Point 4; Point 5'],
                'rubric' => ['criteria' => ['Accuracy', 'Clarity', 'Use of appropriate terms']],
            ],
            default => [
                'type' => 'essay',
                'difficulty' => $difficulty,
                'stem' => "($num) Discuss the concept from this material in detail and justify your points with examples: \"$context\"",
                'options' => null,
                'answer_key' => ['sample_outline' => ['Introduction', 'Main argument', 'Evidence/examples', 'Conclusion']],
                'rubric' => ['criteria' => ['Depth of analysis', 'Organization', 'Use of evidence', 'Language quality']],
            ],
        };
    }

    private function normalize(string $text, int $maxLen = 4000): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        $text = preg_replace('/[^\P{C}\n\r\t]+/u', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim(mb_substr($text, 0, $maxLen));
    }

    private function buildMcqOptions(int $num): array
    {
        $options = [
            ['text' => 'Direct interpretation of the core concept.', 'is_correct' => true],
            ['text' => 'Partially related but incomplete interpretation for many beginner learners.', 'is_correct' => false],
            ['text' => 'A likely misconception learners may have during quick revision.', 'is_correct' => false],
            ['text' => 'An unrelated interpretation that does not match the concept presented.', 'is_correct' => false],
            ['text' => 'A broad but weak statement that sounds correct without proving the key point clearly.', 'is_correct' => false],
        ];

        usort($options, function (array $a, array $b) use ($num) {
            $aw = str_word_count($a['text']);
            $bw = str_word_count($b['text']);
            return $num % 2 === 0 ? ($bw <=> $aw) : ($aw <=> $bw); // alternate largest->smallest / smallest->largest
        });

        $labels = ['A', 'B', 'C', 'D', 'E'];
        foreach ($options as $idx => &$option) {
            $option['label'] = $labels[$idx] ?? chr(65 + $idx);
        }
        unset($option);

        return array_map(fn ($opt) => ['label' => $opt['label'], 'text' => $opt['text'], 'is_correct' => $opt['is_correct']], $options);
    }

    private function mcqCorrectLabel(int $num): string
    {
        $options = $this->buildMcqOptions($num);
        foreach ($options as $option) {
            if (!empty($option['is_correct'])) {
                return $option['label'];
            }
        }
        return 'A';
    }
}

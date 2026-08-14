<?php

namespace App\Services;

use App\Support\AcademicSession;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentFinancialStatementService
{
    /**
     * @return list<int>
     */
    public function availableAcademicYears(Student $student): array
    {
        $years = $student->ledgerEntries()
            ->get()
            ->map(fn (LedgerEntry $e) => $this->academicYearStartFromDate($e->created_at))
            ->merge(
                $student->payments()->pluck('academic_year')->filter()
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($years === []) {
            $years[] = \App\Support\AcademicSession::currentStartYear();
        }

        return $years;
    }

    public function defaultAcademicYear(Student $student): int
    {
        $years = $this->availableAcademicYears($student);

        return (int) ($years[array_key_last($years)] ?? \App\Support\AcademicSession::defaultStartYear());
    }

    /**
     * @return array{
     *     academic_year: int,
     *     academic_year_label: string,
     *     semesters: list<array{
     *         period: int,
     *         title: string,
     *         academic_year_label: string,
     *         sections: list<array{title: string, rows: list<array<string, mixed>>}>
     *     }>,
     *     annual_balance: array{fee: ?float, payment: ?float, balance: ?float},
     *     cumulative_balance: array{fee: float, payment: float, balance: ?float},
     *     has_entries: bool
     * }
     */
    public function build(Student $student, int $academicYearStart): array
    {
        $yearEnd = Carbon::create($academicYearStart + 1, 6, 30, 23, 59, 59);

        $allEntries = $student->ledgerEntries()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $yearEntries = $allEntries->filter(
            fn (LedgerEntry $e) => $this->academicYearStartFromDate($e->created_at) === $academicYearStart
        );

        $cumulativeEntries = $allEntries->filter(
            fn (LedgerEntry $e) => $e->created_at <= $yearEnd
        );

        $payments = Payment::query()
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('id');

        $semesterModels = Semester::query()
            ->where('academic_year', $academicYearStart)
            ->orderBy('number')
            ->get();

        $semesterBlocks = $this->buildSemesterBlocks($yearEntries, $payments, $semesterModels, $academicYearStart);

        $yearTotals = $this->totalsFromRows($this->flattenRows($semesterBlocks));
        $cumulativeTotals = $this->totalsFromEntries($cumulativeEntries);

        return [
            'academic_year' => $academicYearStart,
            'academic_year_label' => $academicYearStart.'/'.($academicYearStart + 1),
            'semesters' => $semesterBlocks,
            'annual_balance' => [
                'fee' => null,
                'payment' => null,
                'balance' => $yearTotals['balance'],
            ],
            'cumulative_balance' => [
                'fee' => $cumulativeTotals['fee'],
                'payment' => $cumulativeTotals['payment'],
                'balance' => $cumulativeTotals['balance'],
            ],
            'has_entries' => $yearEntries->isNotEmpty(),
        ];
    }

    /**
     * @param  Collection<int, LedgerEntry>  $yearEntries
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Semester>  $semesterModels
     * @return list<array{period: int, title: string, academic_year_label: string, sections: list<array{title: string, rows: list<array<string, mixed>>}>}>
     */
    private function buildSemesterBlocks(
        Collection $yearEntries,
        Collection $payments,
        Collection $semesterModels,
        int $academicYearStart
    ): array {
        $grouped = [
            Semester::PERIOD_FIRST => ['fee' => [], 'other' => []],
            Semester::PERIOD_SECOND => ['fee' => [], 'other' => []],
        ];

        foreach ($yearEntries as $entry) {
            $period = $this->resolveSemesterPeriod($entry, $semesterModels, $academicYearStart);
            $row = $this->mapEntryToRow($entry, $payments);
            if ($this->isOtherPaymentEntry($entry)) {
                $grouped[$period]['other'][] = $row;
            } else {
                $grouped[$period]['fee'][] = $row;
            }
        }

        $yearLabel = $academicYearStart.'/'.($academicYearStart + 1).' Academic Year';
        $blocks = [];
        $sn = 0;

        foreach ([Semester::PERIOD_FIRST, Semester::PERIOD_SECOND] as $period) {
            $feeRows = $this->sortFeeRowsByCategory($grouped[$period]['fee']);
            $otherRows = $grouped[$period]['other'];

            $sections = [];
            if ($feeRows !== []) {
                $sections[] = [
                    'title' => "Student's Fee Payments",
                    'rows' => $this->numberRows($feeRows, $sn),
                ];
            }
            if ($otherRows !== []) {
                $sections[] = [
                    'title' => "Student's Other Payments",
                    'rows' => $this->numberRows($otherRows, $sn),
                ];
            }

            $blocks[] = [
                'period' => $period,
                'title' => $period === Semester::PERIOD_FIRST ? 'Semester One' : 'Semester Two',
                'academic_year_label' => $yearLabel,
                'sections' => $sections,
            ];
        }

        return $blocks;
    }

    /**
     * Order a semester's fee rows Tuition Fee, then NHIF, then NACTVET QA (numbers 1, 2, 3),
     * so those categories always land in that fixed order regardless of billing date.
     * Bill/receipt pairs within the same category keep their original chronological order.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortFeeRowsByCategory(array $rows): array
    {
        $rank = [
            'Tuition Fees' => 1,
            'NHIF — Health Insurance' => 2,
            'NACTVET Quality Assurance' => 3,
        ];

        usort($rows, fn ($a, $b) => ($rank[$a['payment_type']] ?? 99) <=> ($rank[$b['payment_type']] ?? 99));

        return $rows;
    }

    /**
     * @param  Collection<int, Semester>  $semesterModels
     */
    private function resolveSemesterPeriod(LedgerEntry $entry, Collection $semesterModels, int $academicYearStart): int
    {
        $at = $entry->created_at;

        foreach ($semesterModels as $semester) {
            if ($semester->start_date && $semester->end_date
                && $at->between($semester->start_date->startOfDay(), $semester->end_date->endOfDay())) {
                return (int) $semester->number;
            }
        }

        $month = (int) $at->format('n');
        $year = (int) $at->format('Y');

        if ($year === $academicYearStart && $month >= 7) {
            return Semester::PERIOD_FIRST;
        }
        if ($year === $academicYearStart + 1 && $month <= 6) {
            return $month <= 2 ? Semester::PERIOD_FIRST : Semester::PERIOD_SECOND;
        }

        return Semester::PERIOD_FIRST;
    }

    /**
     * @param  list<array{sections: list<array{rows: list<array<string, mixed>>}>}>  $semesterBlocks
     * @return list<array<string, mixed>>
     */
    private function flattenRows(array $semesterBlocks): array
    {
        $rows = [];
        foreach ($semesterBlocks as $block) {
            foreach ($block['sections'] as $section) {
                foreach ($section['rows'] as $row) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{fee: float, payment: float, balance: ?float}
     */
    private function totalsFromRows(array $rows): array
    {
        $fee = 0.0;
        $payment = 0.0;
        $balance = null;

        foreach ($rows as $row) {
            if ($row['fee'] !== null) {
                $fee += (float) $row['fee'];
            }
            if ($row['payment'] !== null) {
                $payment += (float) $row['payment'];
            }
            if ($row['balance'] !== null) {
                $balance = (float) $row['balance'];
            }
        }

        return ['fee' => $fee, 'payment' => $payment, 'balance' => $balance];
    }

    /**
     * @param  Collection<int, LedgerEntry>  $entries
     * @return array{fee: float, payment: float, balance: ?float}
     */
    private function totalsFromEntries(Collection $entries): array
    {
        $fee = 0.0;
        $payment = 0.0;
        $balance = null;

        foreach ($entries as $entry) {
            if ($entry->type === 'debit') {
                $fee += (float) $entry->amount;
            } else {
                $payment += (float) $entry->amount;
            }
            if ($entry->balance_after !== null) {
                $balance = (float) $entry->balance_after;
            }
        }

        return ['fee' => $fee, 'payment' => $payment, 'balance' => $balance];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function numberRows(array $rows, int &$sn): array
    {
        foreach ($rows as &$row) {
            $row['sn'] = ++$sn;
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<string, mixed>
     */
    private function mapEntryToRow(LedgerEntry $entry, Collection $payments): array
    {
        $isCredit = $entry->type === 'credit';
        $payment = $isCredit && $entry->reference_type === 'payment' && $entry->reference_id
            ? $payments->get($entry->reference_id)
            : null;

        $fee = $isCredit ? null : (float) $entry->amount;
        $paymentAmount = $isCredit ? (float) $entry->amount : null;

        $balance = $entry->balance_after !== null
            ? (float) $entry->balance_after
            : null;

        return [
            'sn' => 0,
            'date' => $entry->created_at->format('d/m/Y'),
            'transaction_type' => $isCredit ? 'Receipt' : 'Bill',
            'payment_type' => $this->paymentTypeLabel($entry, $payment),
            'remark' => $this->remarkLabel($entry, $payment),
            'reference_no' => $this->referenceNumber($entry, $payment),
            'fee' => $fee,
            'payment' => $paymentAmount,
            'balance' => $balance,
        ];
    }

    private function paymentTypeLabel(LedgerEntry $entry, ?Payment $payment): string
    {
        $text = strtolower($entry->description ?? '');

        if (str_contains($text, 'accommodation') || str_contains($text, 'hostel')) {
            return 'Accommodation Fees';
        }
        if (str_contains($text, 'nhif')) {
            return 'NHIF — Health Insurance';
        }
        if (str_contains($text, 'nactvet') || str_contains($text, 'quality assurance')) {
            return 'NACTVET Quality Assurance';
        }
        if (str_contains($text, 'tuition') || str_contains($text, 'fees charged') || str_contains($text, 'fee assessment')) {
            return 'Tuition Fees';
        }

        if ($payment && is_array($payment->allocation)) {
            $keys = array_keys(array_filter($payment->allocation, fn ($v) => (float) $v > 0));
            if (in_array('accommodation', $keys, true)) {
                return 'Accommodation Fees';
            }
            if (in_array('nhif', $keys, true)) {
                return 'NHIF — Health Insurance';
            }
            if (in_array('nactvet_qa', $keys, true)) {
                return 'NACTVET Quality Assurance';
            }
        }

        return 'Tuition Fees';
    }

    private function remarkLabel(LedgerEntry $entry, ?Payment $payment): string
    {
        if ($entry->type === 'debit') {
            return 'Standard Bill';
        }

        if ($payment) {
            return Payment::methods()[$payment->payment_method] ?? 'Payment';
        }

        return 'Private';
    }

    private function referenceNumber(LedgerEntry $entry, ?Payment $payment): string
    {
        if ($payment && filled($payment->reference)) {
            return (string) $payment->reference;
        }

        if ($entry->type === 'credit' && $entry->reference_id) {
            return (string) $entry->reference_id;
        }

        return '—';
    }

    private function isOtherPaymentEntry(LedgerEntry $entry): bool
    {
        $text = strtolower($entry->description ?? '');

        return str_contains($text, 'accommodation')
            || str_contains($text, 'hostel')
            || ($entry->reference_type === 'charge' && str_contains($text, 'accommodation'));
    }

    private function academicYearStartFromDate(\DateTimeInterface $date): int
    {
        return AcademicSession::currentStartYear($date);
    }
}

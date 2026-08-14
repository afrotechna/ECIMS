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

        $yearTotals = $this->totalsFromEntries($yearEntries);
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
            $rows = $this->mapEntryToRows($entry, $payments);
            $bucket = $this->isOtherPaymentEntry($entry) ? 'other' : 'fee';
            foreach ($rows as $row) {
                $grouped[$period][$bucket][] = $row;
            }
        }

        $yearLabel = $academicYearStart.'/'.($academicYearStart + 1).' Academic Year';
        $blocks = [];
        $sn = 0;

        foreach ([Semester::PERIOD_FIRST, Semester::PERIOD_SECOND] as $period) {
            $feeRows = $this->mergeRowsByCategory($this->sortFeeRowsByCategory($grouped[$period]['fee']));
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
     * Collapse the Bill and Receipt rows for the same category into a single row showing
     * both the billed and paid amount, so each category appears once per semester instead
     * of once per transaction. Assumes rows are already grouped by category (i.e. called
     * after sortFeeRowsByCategory), so rows for the same category are adjacent.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function mergeRowsByCategory(array $rows): array
    {
        $merged = [];
        $order = [];

        foreach ($rows as $row) {
            $key = $row['payment_type'];
            if (! isset($merged[$key])) {
                $order[] = $key;
                $merged[$key] = [
                    'date' => $row['date'],
                    'payment_type' => $key,
                    'remark' => $row['remark'],
                    'reference_no' => $row['reference_no'],
                    'fee' => 0.0,
                    'payment' => 0.0,
                ];
            }

            $merged[$key]['date'] = $row['date'];
            if ($row['fee'] !== null) {
                $merged[$key]['fee'] += (float) $row['fee'];
            }
            if ($row['payment'] !== null) {
                $merged[$key]['payment'] += (float) $row['payment'];
                $merged[$key]['remark'] = $row['remark'];
                if ($row['reference_no'] !== '—') {
                    $merged[$key]['reference_no'] = $row['reference_no'];
                }
            }
        }

        $result = [];
        foreach ($order as $key) {
            $m = $merged[$key];
            $balance = round($m['fee'] - $m['payment'], 2);
            $result[] = [
                'sn' => 0,
                'date' => $m['date'],
                'transaction_type' => $balance <= 0 ? 'Paid' : ($m['payment'] > 0 ? 'Partially Paid' : 'Billed'),
                'payment_type' => $m['payment_type'],
                'remark' => $m['remark'],
                'reference_no' => $m['reference_no'],
                'fee' => $m['fee'],
                'payment' => $m['payment'],
                'balance' => $balance,
            ];
        }

        return $result;
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
     * Category key => display label, in the fixed order rows should appear when an entry
     * covers more than one fee type (Tuition, then NHIF, then NACTVET QA).
     */
    private const CATEGORY_LABELS = [
        'tuition' => 'Tuition Fees',
        'nhif' => 'NHIF — Health Insurance',
        'nactvet_qa' => 'NACTVET Quality Assurance',
        'accommodation' => 'Accommodation Fees',
    ];

    /**
     * A single Bill/Receipt ledger entry can cover several fee categories at once (e.g. one
     * combined payment for tuition + NHIF + NACTVET QA). Split it into one row per category
     * instead of lumping the whole amount under whichever category matched first, so each
     * category always shows its own correct amount.
     *
     * @param  Collection<int, Payment>  $payments
     * @return list<array<string, mixed>>
     */
    private function mapEntryToRows(LedgerEntry $entry, Collection $payments): array
    {
        $isCredit = $entry->type === 'credit';
        $payment = $isCredit && $entry->reference_type === 'payment' && $entry->reference_id
            ? $payments->get($entry->reference_id)
            : null;

        $breakdown = $isCredit && $payment && is_array($payment->allocation)
            ? $this->allocationBreakdown($payment->allocation)
            : $this->allocationFromDescription((string) ($entry->description ?? ''));

        if (count($breakdown) < 2) {
            return [$this->mapEntryToRow($entry, $payments)];
        }

        $balance = $entry->balance_after !== null ? (float) $entry->balance_after : null;
        $lastIndex = count($breakdown) - 1;
        $rows = [];

        foreach (array_values($breakdown) as $i => [$label, $amount]) {
            $rows[] = [
                'sn' => 0,
                'date' => $entry->created_at->format('d/m/Y'),
                'transaction_type' => $isCredit ? 'Receipt' : 'Bill',
                'payment_type' => $label,
                'remark' => $this->remarkLabel($entry, $payment),
                'reference_no' => $this->referenceNumber($entry, $payment),
                'fee' => $isCredit ? null : $amount,
                'payment' => $isCredit ? $amount : null,
                'balance' => $i === $lastIndex ? $balance : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $allocation
     * @return list<array{0: string, 1: float}> label => amount pairs, in CATEGORY_LABELS order
     */
    private function allocationBreakdown(array $allocation): array
    {
        $breakdown = [];
        foreach (self::CATEGORY_LABELS as $key => $label) {
            $amount = (float) ($allocation[$key] ?? 0);
            if ($amount > 0) {
                $breakdown[] = [$label, $amount];
            }
        }

        return $breakdown;
    }

    /**
     * Parses the "key: 123,456; key: 789" breakdown embedded in combined bill/payment
     * descriptions generated by RecordPaymentService (e.g. "Fees charged — Bank Transfer
     * (tuition: 595,000; nhif: 50,400; nactvet_qa: 20,000)").
     *
     * @return list<array{0: string, 1: float}> label => amount pairs, in CATEGORY_LABELS order
     */
    private function allocationFromDescription(string $description): array
    {
        $breakdown = [];
        foreach (self::CATEGORY_LABELS as $key => $label) {
            if (! preg_match('/'.preg_quote($key, '/').':\s*([\d,]+(?:\.\d+)?)/i', $description, $m)) {
                continue;
            }
            $amount = (float) str_replace(',', '', $m[1]);
            if ($amount > 0) {
                $breakdown[] = [$label, $amount];
            }
        }

        return $breakdown;
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

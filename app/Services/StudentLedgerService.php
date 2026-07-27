<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Student;

/**
 * Keeps fee ledger in balance: payments must have matching fee debits (debits − credits = amount owed).
 */
class StudentLedgerService
{
    /**
     * Post opening debit when credits exist without enough debits (legacy payments).
     */
    public function syncOpeningDebits(Student $student): int
    {
        $debits = (float) $student->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $student->ledgerEntries()->where('type', 'credit')->sum('amount');
        $gap = (int) round($credits - $debits);
        if ($gap <= 0) {
            return 0;
        }

        $balanceAfter = (int) round($debits + $gap - $credits);
        LedgerEntry::create([
            'student_id' => $student->id,
            'type' => 'debit',
            'amount' => $gap,
            'description' => 'Fee assessment (ledger sync — matches recorded payments)',
            'reference_type' => 'fee_assessment',
            'reference_id' => null,
            'balance_after' => $balanceAfter,
        ]);

        return $gap;
    }

    /**
     * Before recording a payment credit, ensure debits cover existing credits and this payment.
     */
    public function postFeeDebitForPayment(Student $student, float $amount, string $description): void
    {
        $this->syncOpeningDebits($student);

        $debits = (float) $student->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $student->ledgerEntries()->where('type', 'credit')->sum('amount');
        $rounded = (int) round($amount);
        $balanceAfter = (int) round($debits + $rounded - $credits);

        LedgerEntry::create([
            'student_id' => $student->id,
            'type' => 'debit',
            'amount' => $rounded,
            'description' => $description,
            'reference_type' => 'fee_charge',
            'reference_id' => null,
            'balance_after' => $balanceAfter,
        ]);
    }
}

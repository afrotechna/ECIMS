<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentLedgerService;
use Illuminate\Console\Command;

class SyncStudentLedgerDebitsCommand extends Command
{
    protected $signature = 'ledger:sync-opening-debits {--student= : Student ID to fix only}';

    protected $description = 'Post fee debits where payments were recorded without matching charges (fixes negative balances)';

    public function handle(StudentLedgerService $ledger): int
    {
        $query = Student::query()->whereHas('ledgerEntries');
        if ($id = $this->option('student')) {
            $query->where('id', $id);
        }

        $fixed = 0;
        $total = 0;
        foreach ($query->get() as $student) {
            $amount = $ledger->syncOpeningDebits($student);
            if ($amount > 0) {
                $fixed++;
                $total += $amount;
                $this->line("Student #{$student->id} ({$student->reg_no}): +{$amount} TZS debit");
            }
        }

        $this->info("Done. {$fixed} student(s) updated, {$total} TZS total debits posted.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\FeeStructure;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes student population, all payments/ledger/registrations/results tied to students,
 * and fee-structure rows. Keeps programmes, semesters, courses (modules), staff users, and other non-student data.
 */
class ResetStudentsAndPaymentsCommand extends Command
{
    protected $signature = 'srs:reset-students-and-payments
                            {--force : Required to execute (safety)}
                            {--yes : Skip confirmation prompt (non-interactive)}';

    protected $description = 'Delete all students and payment/fee data. Keeps programmes, semesters, courses (modules), and staff accounts.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to reset: pass --force to confirm this destructive operation.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('yes')) {
            $this->warn('You are in production. Add --yes after reviewing the impact.');
        }

        if (! $this->option('yes') && ! $this->confirm('This PERMANENTLY deletes every student and all related payments, ledger, registrations, results, etc., plus fee structures. Programmes, semesters, and modules stay. Continue?')) {
            return self::FAILURE;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            $this->error('Unsupported database driver: '.$driver.'. Use mysql, mariadb, or sqlite.');

            return self::FAILURE;
        }

        $deletedStudents = 0;
        $deletedStudentUsers = 0;
        $deletedFeeStructures = 0;
        $prunedRegistrations = 0;
        $prunedPayments = 0;
        $prunedLedger = 0;

        DB::transaction(function () use (&$deletedStudents, &$deletedStudentUsers, &$deletedFeeStructures, &$prunedRegistrations, &$prunedPayments, &$prunedLedger) {
            // Do not disable foreign keys when deleting students — MySQL would skip CASCADE and leave orphan rows.
            $prunedRegistrations = SemesterRegistration::query()->whereDoesntHave('student')->delete();
            $prunedPayments = Payment::query()->whereDoesntHave('student')->delete();
            $prunedLedger = LedgerEntry::query()->whereDoesntHave('student')->delete();

            $deletedStudents = Student::query()->delete();

            $deletedStudentUsers = User::query()
                ->where('role', 'student')
                ->delete();

            $deletedFeeStructures = FeeStructure::query()->delete();
        });

        if ($prunedRegistrations > 0) {
            $this->warn("Removed {$prunedRegistrations} semester registration row(s) with missing student (orphans).");
        }
        if ($prunedPayments > 0) {
            $this->warn("Removed {$prunedPayments} payment row(s) with missing student (orphans).");
        }
        if ($prunedLedger > 0) {
            $this->warn("Removed {$prunedLedger} ledger row(s) with missing student (orphans).");
        }

        $this->info("Removed {$deletedStudents} student row(s), {$deletedStudentUsers} student user account(s), {$deletedFeeStructures} fee structure row(s) (semester lines cascade).");
        $this->comment('Preserved: programmes, semesters, courses, course–semester links, staff users, hostels, announcements, calendar, and other non-student tables.');

        return self::SUCCESS;
    }
}

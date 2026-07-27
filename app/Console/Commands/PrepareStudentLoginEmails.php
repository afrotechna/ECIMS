<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fill missing student emails so admin can bulk-create user logins (Users → create all student logins).
 */
class PrepareStudentLoginEmails extends Command
{
    protected $signature = 'students:prepare-login-emails
                            {--domain=student.cohas.local : Email domain suffix}
                            {--dry-run : List changes without saving}';

    protected $description = 'Set placeholder emails on active students who have no email (for user account creation)';

    public function handle(): int
    {
        $domain = ltrim((string) $this->option('domain'), '@');
        if ($domain === '') {
            $this->error('Domain must not be empty.');

            return self::FAILURE;
        }

        $students = Student::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('email')->orWhere('email', '');
            })
            ->orderBy('reg_no')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No active students without email.');

            return self::SUCCESS;
        }

        $used = Student::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->flip();

        $updated = 0;
        foreach ($students as $student) {
            $local = $this->localPart($student);
            if ($local === '') {
                $this->warn("Skip id {$student->id}: no reg_no or NACTVET number for email.");

                continue;
            }
            $email = strtolower($local).'@'.$domain;
            $base = $email;
            $n = 1;
            while ($used->has($email)) {
                $n++;
                $email = strtolower($local).$n.'@'.$domain;
            }
            if ($this->option('dry-run')) {
                $this->line("{$student->reg_no} → {$email}");
                $used->put($email, true);
                $updated++;

                continue;
            }
            $student->update(['email' => $email]);
            $used->put($email, true);
            $updated++;
        }

        $verb = $this->option('dry-run') ? 'Would update' : 'Updated';
        $this->info("{$verb} {$updated} student(s). Next: Users → Create logins for all students (or users.create-all-student-logins).");

        return self::SUCCESS;
    }

    private function localPart(Student $student): string
    {
        $nactvet = trim((string) $student->nactvet_reg_no);
        if ($nactvet !== '') {
            return Str::slug($nactvet, '');
        }
        $reg = trim((string) $student->reg_no);

        return $reg !== '' ? Str::slug($reg, '') : '';
    }
}

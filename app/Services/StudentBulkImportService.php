<?php

namespace App\Services;

use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Support\SimpleXlsxReader;
use App\Support\StudentRegistrationNumber;
use Illuminate\Support\Facades\DB;

class StudentBulkImportService
{
    /**
     * @return array{deleted: int, created: int, errors: list<string>}
     */
    public function replaceProgrammeLevelFromSpreadsheet(
        string $filePath,
        string $programmeCode,
        int $ntaLevel,
        ?int $defaultIntakeYear = null,
    ): array {
        $programme = Programme::where('is_active', true)->where('code', strtoupper($programmeCode))->first();
        if (! $programme) {
            throw new \InvalidArgumentException('Programme '.$programmeCode.' not found or inactive.');
        }

        $rows = SimpleXlsxReader::sheetRows($filePath);
        $parsed = $this->parseEnrolledListRows($rows);

        return DB::transaction(function () use ($programme, $ntaLevel, $defaultIntakeYear, $parsed) {
            $deleted = $this->deleteProgrammeLevelStudents($programme->id, $ntaLevel);
            $created = 0;
            $errors = [];

            foreach ($parsed as $i => $row) {
                $label = 'Row '.($i + 1);
                try {
                    $this->createStudentRow($row, $programme, $ntaLevel, $defaultIntakeYear);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = $label.': '.$e->getMessage();
                }
            }

            return ['deleted' => $deleted, 'created' => $created, 'errors' => $errors];
        });
    }

    public function deleteProgrammeLevelStudents(int $programmeId, int $ntaLevel): int
    {
        $students = Student::query()
            ->where('programme_id', $programmeId)
            ->where('nta_level', $ntaLevel)
            ->get();

        $emails = $students->pluck('email')->filter()->unique()->values();

        if ($emails->isNotEmpty()) {
            User::query()->where('role', 'student')->whereIn('email', $emails)->delete();
        }

        $ids = $students->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        return Student::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param  list<list<string|null>>  $rows
     * @return list<array{nactvet_reg_no: string, first_name: string, last_name: string, gender: ?string}>
     */
    public function parseEnrolledListRows(array $rows): array
    {
        $headerIndex = null;
        $colMap = [];

        foreach ($rows as $i => $row) {
            $labels = array_map(fn ($c) => $this->normalizeHeader((string) ($c ?? '')), $row);
            $regIdx = $this->findColumn($labels, ['registration', 'registration number', 'registration #', 'nactvet reg no', 'nacte registration', 'reg no']);
            $nameIdx = $this->findColumn($labels, ['full name', 'candidate', 'candidat', 'student name', 'name']);
            if ($regIdx !== null && $nameIdx !== null) {
                $headerIndex = $i;
                $colMap = [
                    'reg' => $regIdx,
                    'name' => $nameIdx,
                    'gender' => $this->findColumn($labels, ['gender', 'sex']),
                ];
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find header row (Registration # and Full Name).');
        }

        $out = [];
        for ($r = $headerIndex + 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            $reg = trim((string) ($row[$colMap['reg']] ?? ''));
            $fullName = trim((string) ($row[$colMap['name']] ?? ''));
            if ($reg === '' || $fullName === '') {
                continue;
            }
            if (preg_match('/^(sn|s\/n|total|grand)/i', $reg)) {
                continue;
            }
            [$first, $last] = $this->splitName($fullName);
            $gender = null;
            if ($colMap['gender'] !== null) {
                $gender = $this->normalizeGender((string) ($row[$colMap['gender']] ?? ''));
            }
            $out[] = [
                'nactvet_reg_no' => strtoupper($reg),
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
            ];
        }

        if ($out === []) {
            throw new \RuntimeException('No student rows found below the header.');
        }

        return $out;
    }

    /**
     * @param  array{nactvet_reg_no: string, first_name: string, last_name: string, gender: ?string}  $row
     */
    private function createStudentRow(array $row, Programme $programme, int $ntaLevel, ?int $defaultIntakeYear): void
    {
        $nactvet = $row['nactvet_reg_no'];
        if (! $this->isValidRegNo($nactvet)) {
            throw new \InvalidArgumentException('Invalid registration: '.$nactvet);
        }
        if (Student::where('nactvet_reg_no', $nactvet)->exists()) {
            throw new \InvalidArgumentException('Registration already exists: '.$nactvet);
        }

        $intakeYear = $defaultIntakeYear ?? $this->guessIntakeYear($nactvet);
        $regNo = $this->generateRegNo($intakeYear, $programme->code);

        Student::create([
            'reg_no' => $regNo,
            'nactvet_reg_no' => $nactvet,
            'form_four_index' => $nactvet,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $row['gender'],
            'programme_id' => $programme->id,
            'intake_year' => $intakeYear,
            'nta_level' => $ntaLevel,
            'status' => 'active',
            'student_type' => 'regular',
        ]);
    }

    private function generateRegNo(int $intakeYear, string $programmeCode): string
    {
        $prefix = 'MCHAS-'.$intakeYear.'-'.strtoupper($programmeCode);
        $last = Student::where('reg_no', 'like', $prefix.'%')->orderByDesc('id')->first();
        $seq = $last ? (int) substr($last->reg_no, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function isValidRegNo(string $n): bool
    {
        return StudentRegistrationNumber::isValid($n);
    }

    private function guessIntakeYear(string $regNo): int
    {
        if (preg_match('/\/(\d{4})\s*$/', $regNo, $m)) {
            $y = (int) $m[1];
            if ($y >= 1990 && $y <= 2100) {
                return $y;
            }
        }

        return (int) date('Y');
    }

    private function normalizeHeader(string $h): string
    {
        $h = str_replace(["\xC2\xA0", '#'], [' ', ''], $h);
        $h = trim(preg_replace('/\s+/', ' ', strtolower($h)));

        return $h;
    }

    /**
     * @param  list<string>  $labels
     * @param  list<string>  $needles
     */
    private function findColumn(array $labels, array $needles): ?int
    {
        foreach ($labels as $i => $label) {
            foreach ($needles as $needle) {
                if ($label === $needle || str_contains($label, $needle)) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return ['Student', 'Unknown'];
        }
        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name, 2));

            return [$parts[1] ?: $parts[0], $parts[0] ?: $parts[1]];
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
    }

    private function normalizeGender(string $raw): ?string
    {
        $g = strtolower(trim($raw));
        if ($g === '') {
            return null;
        }
        if (in_array($g, ['m', 'male'], true)) {
            return 'M';
        }
        if (in_array($g, ['f', 'female'], true)) {
            return 'F';
        }

        return null;
    }
}

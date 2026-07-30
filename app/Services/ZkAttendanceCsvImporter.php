<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentAttendanceLog;
use Carbon\Carbon;

/**
 * Imports ZKTeco biometric terminal attendance exports. ZKTeco software variants differ in
 * exact column names, so headers are matched against known alias groups rather than one
 * fixed spelling. Students are resolved via Student::biometric_id (the device's enrolled
 * User ID), which is unrelated to reg_no and must be mapped separately.
 */
class ZkAttendanceCsvImporter
{
    /** @var list<string> */
    private array $errors = [];

    private const ID_ALIASES = ['userid', 'user id', 'id', 'pin', 'enroll no', 'enrollno', 'badge number', 'badgenumber'];

    private const TIME_ALIASES = ['time', 'datetime', 'date time', 'punch time', 'punchtime', 'check time', 'checktime'];

    private const DIRECTION_ALIASES = ['state', 'status', 'in/out', 'inout', 'c/i c/o'];

    private const NAME_ALIASES = ['name'];

    /**
     * @return array{rows_touched: int, rows_skipped: int, errors: list<string>}
     */
    public function import(string $absolutePath, ?int $importLogId = null): array
    {
        $this->errors = [];

        if (! is_readable($absolutePath)) {
            return ['rows_touched' => 0, 'rows_skipped' => 0, 'errors' => ['File could not be read.']];
        }

        $fh = new \SplFileObject($absolutePath, 'r');
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);

        $headerRow = $fh->fgetcsv();
        if ($headerRow === false || $headerRow === null) {
            return ['rows_touched' => 0, 'rows_skipped' => 0, 'errors' => ['File is empty.']];
        }

        $columns = $this->mapColumns($headerRow);
        if ($columns['id'] === null || $columns['time'] === null) {
            $found = implode(', ', array_map('trim', $headerRow));

            return [
                'rows_touched' => 0,
                'rows_skipped' => 0,
                'errors' => ["Could not find required User ID / Time columns. Headers found in file: {$found}"],
            ];
        }

        $rowsTouched = 0;
        $rowsSkipped = 0;

        while (! $fh->eof()) {
            $row = $fh->fgetcsv();
            if ($row === false || $row === null || $row === [null]) {
                continue;
            }

            $deviceId = trim((string) ($row[$columns['id']] ?? ''));
            $timeRaw = trim((string) ($row[$columns['time']] ?? ''));
            if ($deviceId === '' || $timeRaw === '') {
                continue;
            }

            $name = $columns['name'] !== null ? trim((string) ($row[$columns['name']] ?? '')) : null;

            try {
                $punchedAt = Carbon::parse($timeRaw);
            } catch (\Throwable) {
                $this->errors[] = "Device ID {$deviceId}: could not parse time \"{$timeRaw}\".";
                $rowsSkipped++;

                continue;
            }

            $student = Student::where('biometric_id', $deviceId)->first();
            if (! $student) {
                $label = $name ? "{$deviceId} ({$name})" : $deviceId;
                $this->errors[] = "Device ID {$label} at {$punchedAt->toDateTimeString()}: no student mapped to this biometric ID.";
                $rowsSkipped++;

                continue;
            }

            $direction = $columns['direction'] !== null
                ? $this->resolveDirection(trim((string) ($row[$columns['direction']] ?? '')))
                : 'unknown';

            [, $created] = $this->firstOrCreateLog($student, $punchedAt, $direction, $deviceId, $importLogId);
            if ($created) {
                $rowsTouched++;
            }
        }

        return ['rows_touched' => $rowsTouched, 'rows_skipped' => $rowsSkipped, 'errors' => $this->errors];
    }

    /**
     * @param  list<string>  $headerRow
     * @return array{id: ?int, time: ?int, direction: ?int, name: ?int}
     */
    private function mapColumns(array $headerRow): array
    {
        $normalized = array_map(fn ($h) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $h))), $headerRow);

        return [
            'id' => $this->findColumn($normalized, self::ID_ALIASES),
            'time' => $this->findColumn($normalized, self::TIME_ALIASES),
            'direction' => $this->findColumn($normalized, self::DIRECTION_ALIASES),
            'name' => $this->findColumn($normalized, self::NAME_ALIASES),
        ];
    }

    /**
     * @param  list<string>  $normalizedHeaders
     * @param  list<string>  $aliases
     */
    private function findColumn(array $normalizedHeaders, array $aliases): ?int
    {
        foreach ($normalizedHeaders as $idx => $header) {
            if (in_array($header, $aliases, true)) {
                return $idx;
            }
        }

        return null;
    }

    /** ZKTeco's common raw state codes: 0/2/4 are in-type punches, 1/3/5 are out-type. */
    private function resolveDirection(string $value): string
    {
        $v = strtolower(trim($value));
        if ($v === '') {
            return 'unknown';
        }
        if (in_array($v, ['0', '2', '4'], true) || str_contains($v, 'in')) {
            return 'in';
        }
        if (in_array($v, ['1', '3', '5'], true) || str_contains($v, 'out')) {
            return 'out';
        }

        return 'unknown';
    }

    /** @return array{0: StudentAttendanceLog, 1: bool} */
    private function firstOrCreateLog(Student $student, Carbon $punchedAt, string $direction, string $deviceId, ?int $importLogId): array
    {
        $existing = StudentAttendanceLog::where('raw_biometric_id', $deviceId)
            ->where('punched_at', $punchedAt)
            ->first();
        if ($existing) {
            return [$existing, false];
        }

        $log = StudentAttendanceLog::create([
            'student_id' => $student->id,
            'punched_at' => $punchedAt,
            'direction' => $direction,
            'raw_biometric_id' => $deviceId,
            'attendance_import_log_id' => $importLogId,
        ]);

        return [$log, true];
    }
}

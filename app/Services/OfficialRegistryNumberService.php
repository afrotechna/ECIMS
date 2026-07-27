<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Str;

/**
 * One official college registration reference per student for the whole NTA programme (not re-issued each semester).
 */
class OfficialRegistryNumberService
{
    public function assignIfMissing(Student $student): ?string
    {
        if ($student->official_registry_no) {
            return $student->official_registry_no;
        }

        $student->load('programme');
        $code = Str::upper($student->programme->code ?? 'PRG');
        $year = (int) $student->intake_year;
        $prefix = 'COHAS/'.$year.'/'.$code.'/';

        $count = Student::query()
            ->where('programme_id', $student->programme_id)
            ->where('intake_year', $year)
            ->whereNotNull('official_registry_no')
            ->count();

        $seq = $count + 1;
        $ref = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        if (Student::where('official_registry_no', $ref)->exists()) {
            $ref = $prefix.str_pad((string) ($seq + 1), 4, '0', STR_PAD_LEFT);
        }

        $student->update(['official_registry_no' => $ref]);

        return $ref;
    }
}

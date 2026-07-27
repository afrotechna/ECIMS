<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\Support\Collection;

class ResultImportCourseQuery
{
    /**
     * @return Collection<int, Course>
     */
    public static function forTemplate(Semester $semester, Programme $programme, ?int $ntaLevel): Collection
    {
        $base = Course::query()
            ->where('programme_id', $programme->id)
            ->when($ntaLevel, fn ($q) => $q->where('nta_level', $ntaLevel));

        $linked = (clone $base)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $semester->id))
            ->orderBy('code')
            ->get();

        if ($linked->isNotEmpty()) {
            return $linked;
        }

        $byTerm = (clone $base)
            ->whereHas('semesters', fn ($q) => $q->where('number', $semester->number))
            ->orderBy('code')
            ->get();

        if ($byTerm->isNotEmpty()) {
            return $byTerm;
        }

        return $base->orderBy('code')->get();
    }
}

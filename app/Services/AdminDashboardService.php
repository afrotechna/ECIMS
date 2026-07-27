<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Programme;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Support\AcademicSession;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardService
{
    private const NTA_LEVELS = [4, 5, 6];

    /**
     * Active students on a programme with a valid NTA level (matches Students index cohorts).
     */
    private function registryStudentsQuery(): Builder
    {
        return Student::query()
            ->where('status', 'active')
            ->whereNotNull('programme_id')
            ->whereIn('nta_level', self::NTA_LEVELS);
    }

    /**
     * @return array<int, int>
     */
    private function chartByNtaLevel(): array
    {
        $counts = $this->registryStudentsQuery()
            ->selectRaw('nta_level, COUNT(*) as total')
            ->groupBy('nta_level')
            ->pluck('total', 'nta_level');

        $result = [];
        foreach (self::NTA_LEVELS as $level) {
            $result[$level] = (int) ($counts[$level] ?? 0);
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function chartByProgramme(): array
    {
        $counts = $this->registryStudentsQuery()
            ->join('programmes', 'programmes.id', '=', 'students.programme_id')
            ->selectRaw('programmes.code, COUNT(*) as total')
            ->groupBy('programmes.code')
            ->orderBy('programmes.code')
            ->pluck('total', 'code');

        $chart = [];
        foreach (Programme::query()->where('is_active', true)->orderBy('code')->pluck('code') as $code) {
            $chart[$code] = (int) ($counts[$code] ?? 0);
        }

        return $chart;
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function chartByProgrammeLevel(): array
    {
        $rows = $this->registryStudentsQuery()
            ->join('programmes', 'programmes.id', '=', 'students.programme_id')
            ->selectRaw('programmes.code, students.nta_level, COUNT(*) as total')
            ->groupBy('programmes.code', 'students.nta_level')
            ->orderBy('programmes.code')
            ->orderBy('students.nta_level')
            ->get();

        $chart = [];
        foreach (Programme::query()->where('is_active', true)->orderBy('code')->pluck('code') as $code) {
            $chart[$code] = array_fill_keys(self::NTA_LEVELS, 0);
        }

        foreach ($rows as $row) {
            $level = (int) $row->nta_level;
            if (isset($chart[$row->code][$level])) {
                $chart[$row->code][$level] = (int) $row->total;
            }
        }

        return $chart;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $academicYear = AcademicSession::defaultStartYear();
        $chartByNtaLevel = $this->chartByNtaLevel();
        $chartByProgramme = $this->chartByProgramme();
        $chartByProgrammeLevel = $this->chartByProgrammeLevel();
        $registryStudentCount = array_sum($chartByNtaLevel);

        $chartEnrollment = $this->registryStudentsQuery()
            ->selectRaw('intake_year as year, COUNT(*) as total')
            ->groupBy('intake_year')
            ->orderBy('intake_year')
            ->pluck('total', 'year')
            ->toArray();

        $chartPayments = [];
        for ($m = 5; $m >= 0; $m--) {
            $date = now()->subMonths($m);
            $chartPayments[$date->format('M y')] = (int) Payment::query()
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');
        }

        $registrationTrend = [];
        for ($m = 5; $m >= 0; $m--) {
            $date = now()->subMonths($m);
            $key = $date->format('M y');
            $registrationTrend[$key] = (int) SemesterRegistration::query()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        $cleared = 0;
        $inArrears = 0;
        $totalArrears = 0.0;
        foreach ($this->registryStudentsQuery()->get() as $student) {
            $bal = $student->balance;
            if ($bal > 0) {
                $inArrears++;
                $totalArrears += $bal;
            } else {
                $cleared++;
            }
        }

        return [
            'academicYear' => $academicYear,
            'studentCount' => Student::count(),
            'activeStudentCount' => $registryStudentCount,
            'programmeCount' => Programme::where('is_active', true)->count(),
            'paymentsToday' => (int) Payment::whereDate('paid_at', today())->sum('amount'),
            'chartByNtaLevel' => $chartByNtaLevel,
            'chartByProgramme' => $chartByProgramme,
            'chartByProgrammeLevel' => $chartByProgrammeLevel,
            'chartEnrollment' => $chartEnrollment,
            'chartPayments' => $chartPayments,
            'chartRegistrationTrend' => $registrationTrend,
            'chartBalance' => [
                'cleared' => $cleared,
                'in_arrears' => $inArrears,
                'total_arrears' => (int) round($totalArrears),
            ],
        ];
    }
}

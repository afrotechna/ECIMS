<?php

namespace App\Http\Controllers;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRotationRound;
use App\Models\Student;
use App\Services\ClinicalCoordinatorService;
use Illuminate\Http\Request;

class ClinicalCoordinatorController extends Controller
{
    public function dashboard(Request $request, ClinicalCoordinatorService $coordinator)
    {
        $roundId = $request->integer('round_id') ?: null;
        $data = $coordinator->dashboard($roundId);

        return view('clinical.coordinator-dashboard', $data);
    }

    public function reports(Request $request, ClinicalCoordinatorService $coordinator)
    {
        $roundId = $request->integer('round_id') ?: null;
        $sites = $coordinator->siteSummary($roundId);
        $mismatches = $coordinator->attendanceLogbookMismatches($roundId);
        $rounds = ClinicalRotationRound::query()->with('semester', 'programme')->orderByDesc('created_at')->limit(20)->get();

        return view('clinical.reports', compact('sites', 'mismatches', 'rounds', 'roundId'));
    }

    public function exportLogbookCsv(Request $request)
    {
        $round = ClinicalRotationRound::with(['groups.students', 'semester'])->findOrFail($request->integer('round_id'));
        $semesterId = $round->semester_id;

        $lines = [['Reg no', 'Student', 'Group', 'Procedure', 'Date', 'Status', 'Department']];
        foreach ($round->groups as $group) {
            foreach ($group->students as $student) {
                $entries = ClinicalLogbookEntry::query()
                    ->with('procedure')
                    ->where('student_id', $student->id)
                    ->where('semester_id', $semesterId)
                    ->orderBy('performed_on')
                    ->get();
                foreach ($entries as $e) {
                    $lines[] = [
                        $student->reg_no,
                        $student->full_name,
                        $group->name,
                        $e->procedure?->code.' '.$e->procedure?->name,
                        $e->performed_on->format('Y-m-d'),
                        $e->status,
                        $e->department_code ?? '',
                    ];
                }
            }
        }

        $filename = 'clinical-logbook-round-'.$round->id.'.csv';
        $callback = function () use ($lines) {
            $out = fopen('php://output', 'w');
            foreach ($lines as $line) {
                fputcsv($out, $line);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
    }

    public function printStudentLogbook(Student $student, Request $request)
    {
        $semesterId = $request->integer('semester_id') ?: null;
        $entries = ClinicalLogbookEntry::query()
            ->with(['procedure', 'reviewer', 'semester'])
            ->where('student_id', $student->id)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderBy('performed_on')
            ->get();

        $student->load('programme');
        $overview = app(\App\Services\ClinicalSummativeService::class)->overview($student, $semesterId);

        return view('clinical.print-logbook', compact('student', 'entries', 'overview'));
    }
}

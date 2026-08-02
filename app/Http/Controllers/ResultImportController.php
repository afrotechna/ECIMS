<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Programme;
use App\Models\Result;
use App\Models\User;
use App\Notifications\ResultsPendingApprovalNotification;
use App\Services\ResultImportCourseQuery;
use App\Models\ResultImportLog;
use App\Models\Semester;
use App\Services\NactvetResultCsvImporter;
use App\Services\ResultImportCsvTemplate;
use App\Services\ResultImportSpreadsheetExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResultImportController extends Controller
{
    public function createCa(Request $request)
    {
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $programmes = Programme::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $semesterId = $request->get('semester_id');
        $programmeId = $request->get('programme_id');
        $ntaLevel = $request->get('nta_level');
        $courses = $this->coursesForTemplate($semesterId, $programmeId, $ntaLevel);

        return view('results.import-ca', compact('semesters', 'programmes', 'courses', 'semesterId', 'programmeId', 'ntaLevel'));
    }

    public function downloadCaTemplate(Request $request, ResultImportCsvTemplate $templates)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['nullable', 'integer', 'in:4,5,6'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);

        return $templates->downloadCa(
            $semester,
            $programme,
            isset($validated['nta_level']) ? (int) $validated['nta_level'] : null
        );
    }

    public function downloadCaDemoExcel(Request $request, ResultImportCsvTemplate $templates, ResultImportSpreadsheetExport $export)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['required', 'integer', 'in:4,5,6'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);

        return $templates->downloadCaDemoExcel(
            $semester,
            $programme,
            (int) $validated['nta_level'],
            $export
        );
    }

    public function storeCa(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xml', 'max:10240'],
        ]);
        $semester = Semester::findOrFail($validated['semester_id']);
        $path = $request->file('file')->getRealPath();
        $importer = new NactvetResultCsvImporter('ca', $semester);
        $out = $importer->import($path);

        ResultImportLog::create([
            'user_id' => auth()->id(),
            'import_type' => 'ca_csv',
            'semester_id' => $semester->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'rows_touched' => $out['rows_touched'],
            'notes' => count($out['errors']) ? implode("\n", array_slice($out['errors'], 0, 30)) : null,
        ]);

        if (Schema::hasTable('activity_log') && $out['rows_touched'] > 0) {
            ActivityLog::log('result.import_ca', null, null, "semester_id: {$semester->id}, rows: {$out['rows_touched']}");
        }

        if ($out['rows_touched'] > 0) {
            $summary = $this->pendingApprovalSummary($semester, 'ca');
            User::query()
                ->whereIn('role', ['principal', 'vice_principal_arc', 'administrator'])
                ->get()
                ->each
                ->notify(new ResultsPendingApprovalNotification($semester, $out['rows_touched'], $summary));
        }

        $msg = "Imported {$out['rows_touched']} module result(s) (one per student per module on the sheet). Pending approval before students/guardians can see them.";
        if (count($out['errors'])) {
            $msg .= ' Notes: '.implode(' ', array_slice($out['errors'], 0, 5));
            if (count($out['errors']) > 5) {
                $msg .= ' (see import logs for more)';
            }
        }

        return redirect()->route('results.import.ca', ['semester_id' => $semester->id])->with('success', $msg);
    }

    public function createFinal(Request $request)
    {
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $programmes = Programme::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $semesterId = $request->get('semester_id');
        $programmeId = $request->get('programme_id');
        $ntaLevel = $request->get('nta_level');
        $courses = $this->coursesForTemplate($semesterId, $programmeId, $ntaLevel);

        return view('results.import-final', compact('semesters', 'programmes', 'courses', 'semesterId', 'programmeId', 'ntaLevel'));
    }

    public function downloadFinalTemplate(Request $request, ResultImportCsvTemplate $templates)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['nullable', 'integer', 'in:4,5,6'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);

        return $templates->downloadFinal(
            $semester,
            $programme,
            isset($validated['nta_level']) ? (int) $validated['nta_level'] : null
        );
    }

    public function downloadFinalDemoExcel(Request $request, ResultImportCsvTemplate $templates, ResultImportSpreadsheetExport $export)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['required', 'integer', 'in:4,5,6'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);

        return $templates->downloadFinalDemoExcel(
            $semester,
            $programme,
            (int) $validated['nta_level'],
            $export
        );
    }

    private function coursesForTemplate(mixed $semesterId, mixed $programmeId, mixed $ntaLevel)
    {
        if (! $semesterId || ! $programmeId) {
            return collect();
        }

        $semester = Semester::findOrFail((int) $semesterId);
        $programme = Programme::findOrFail((int) $programmeId);

        return ResultImportCourseQuery::forTemplate(
            $semester,
            $programme,
            $ntaLevel ? (int) $ntaLevel : null
        );
    }

    public function storeFinal(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xml', 'max:10240'],
        ]);
        $semester = Semester::findOrFail($validated['semester_id']);
        $path = $request->file('file')->getRealPath();
        $importer = new NactvetResultCsvImporter('final', $semester);
        $out = $importer->import($path);

        ResultImportLog::create([
            'user_id' => auth()->id(),
            'import_type' => 'final_csv',
            'semester_id' => $semester->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'rows_touched' => $out['rows_touched'],
            'notes' => count($out['errors']) ? implode("\n", array_slice($out['errors'], 0, 30)) : null,
        ]);

        if (Schema::hasTable('activity_log') && $out['rows_touched'] > 0) {
            ActivityLog::log('result.import_final', null, null, "semester_id: {$semester->id}, rows: {$out['rows_touched']}");
        }

        if ($out['rows_touched'] > 0) {
            $summary = $this->pendingApprovalSummary($semester, 'final');
            User::query()
                ->whereIn('role', ['principal', 'vice_principal_arc', 'administrator'])
                ->get()
                ->each
                ->notify(new ResultsPendingApprovalNotification($semester, $out['rows_touched'], $summary));
        }

        $msg = "Imported {$out['rows_touched']} module result(s) (one per student per module on the sheet). Pending approval before students/guardians can see them.";
        if (count($out['errors'])) {
            $msg .= ' Notes: '.implode(' ', array_slice($out['errors'], 0, 5));
            if (count($out['errors']) > 5) {
                $msg .= ' (see import logs)';
            }
        }

        return redirect()->route('results.import.final', ['semester_id' => $semester->id])->with('success', $msg);
    }

    /**
     * Snapshot of all pending results for this semester right now, for the approval notification.
     *
     * @return array{total: int, pass: int, fail: int}
     */
    private function pendingApprovalSummary(Semester $semester, string $mode): array
    {
        return Result::query()
            ->where('semester_id', $semester->id)
            ->where('status', 'pending_approval')
            ->with('course')
            ->get()
            ->reduce(function (array $carry, Result $r) use ($mode) {
                $carry['total']++;
                $outcome = $mode === 'ca' ? $r->caModuleRemark() : match ($r->finalOutcome()) {
                    'pass' => 'PASS',
                    'fail' => 'FAIL',
                    default => null,
                };
                if ($outcome === 'FAIL') {
                    $carry['fail']++;
                } elseif ($outcome === 'PASS') {
                    $carry['pass']++;
                }

                return $carry;
            }, ['total' => 0, 'pass' => 0, 'fail' => 0]);
    }
}

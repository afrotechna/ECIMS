<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentAttendanceImportLog;
use App\Models\StudentAttendanceLog;
use App\Services\ZkAttendanceCsvImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date') ?? now()->toDateString();

        $students = Student::where('status', 'active')
            ->when(auth()->user()->hodProgrammeId(), fn ($q, $pid) => $q->where('programme_id', $pid))
            ->orderBy('reg_no')->get();

        $logsForDay = StudentAttendanceLog::whereDate('punched_at', $date)
            ->orderBy('punched_at')
            ->get()
            ->groupBy('student_id');

        $summaries = $logsForDay->map(fn ($logs) => [
            'first' => $logs->first()->punched_at,
            'last' => $logs->last()->punched_at,
            'count' => $logs->count(),
        ]);

        return view('student-attendance.index', compact('students', 'summaries', 'date'));
    }

    public function show(Student $student, Request $request)
    {
        $from = $request->date('from') ?? now()->subDays(30)->toDateString();
        $to = $request->date('to') ?? now()->toDateString();

        $logs = $student->attendanceLogs()
            ->whereDate('punched_at', '>=', $from)
            ->whereDate('punched_at', '<=', $to)
            ->orderByDesc('punched_at')
            ->paginate(50)
            ->withQueryString();

        return view('student-attendance.show', compact('student', 'logs', 'from', 'to'));
    }

    public function createImport()
    {
        return view('student-attendance.import');
    }

    public function storeImport(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $importer = new ZkAttendanceCsvImporter;
        $importLog = StudentAttendanceImportLog::create([
            'user_id' => auth()->id(),
            'original_filename' => $request->file('file')->getClientOriginalName(),
        ]);

        $out = $importer->import($request->file('file')->getRealPath(), $importLog->id);

        $importLog->update([
            'rows_touched' => $out['rows_touched'],
            'rows_skipped' => $out['rows_skipped'],
            'notes' => count($out['errors']) ? implode("\n", array_slice($out['errors'], 0, 30)) : null,
        ]);

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('student_attendance.import', StudentAttendanceImportLog::class, $importLog->id, "rows: {$out['rows_touched']}, skipped: {$out['rows_skipped']}");
        }

        $msg = "Imported {$out['rows_touched']} punch(es), skipped {$out['rows_skipped']}.";
        if (count($out['errors'])) {
            $msg .= ' Notes: '.implode(' ', array_slice($out['errors'], 0, 5));
            if (count($out['errors']) > 5) {
                $msg .= ' (see import log for more)';
            }
        }

        return redirect()->route('student-attendance.import')->with('success', $msg);
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['User ID', 'Name', 'Time', 'State']);
            fputcsv($out, ['1023', 'Jane Student', '2026-08-01 07:58:00', '0']);
            fputcsv($out, ['1023', 'Jane Student', '2026-08-01 17:05:00', '1']);
            fclose($out);
        }, 'zk-attendance-template.csv');
    }

    public function mappingForm()
    {
        $students = Student::where('status', 'active')
            ->when(auth()->user()->hodProgrammeId(), fn ($q, $pid) => $q->where('programme_id', $pid))
            ->orderBy('reg_no')->get();

        return view('student-attendance.mapping', compact('students'));
    }

    public function mappingStore(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $fh = new \SplFileObject($request->file('file')->getRealPath(), 'r');
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);

        $header = $fh->fgetcsv();
        $updated = 0;
        $notFound = [];

        while (! $fh->eof()) {
            $row = $fh->fgetcsv();
            if ($row === false || $row === null || $row === [null] || count($row) < 2) {
                continue;
            }
            $regNo = trim((string) $row[0]);
            $biometricId = trim((string) $row[1]);
            if ($regNo === '' || $biometricId === '' || strcasecmp($regNo, 'reg_no') === 0) {
                continue;
            }

            $student = Student::where('reg_no', $regNo)->first();
            if (! $student) {
                $notFound[] = $regNo;

                continue;
            }
            $student->update(['biometric_id' => $biometricId]);
            $updated++;
        }

        $msg = "Mapped {$updated} student(s).";
        if ($notFound !== []) {
            $msg .= ' Not found: '.implode(', ', array_slice($notFound, 0, 10));
        }

        return redirect()->route('student-attendance.mapping')->with('success', $msg);
    }
}

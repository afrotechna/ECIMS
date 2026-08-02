<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\ResultsRejectedNotification;
use App\Services\ResultApprovalGridBuilder;
use App\Services\ResultReleaseSmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResultApprovalController extends Controller
{
    /**
     * Only the Principal or VP (ARC) may approve/reject results — the roles that already
     * prepare imports (Examination Officer, HODs, VP ARC itself) must not approve their own work,
     * so this is a hard-coded gate rather than relying on the 'results' module permission alone.
     */
    private const APPROVER_ROLES = ['principal', 'vice_principal_arc'];

    private function ensureApproverRole(): void
    {
        $role = User::normalizeRoleSlug((string) auth()->user()->role);
        if (! in_array($role, self::APPROVER_ROLES, true)) {
            abort(403, 'Only the Principal or VP (ARC) can approve or reject results.');
        }
    }

    public function index()
    {
        $canApprove = in_array(User::normalizeRoleSlug((string) auth()->user()->role), self::APPROVER_ROLES, true);

        $pending = Result::query()
            ->where('status', 'pending_approval')
            ->selectRaw('semester_id, COUNT(*) as row_count')
            ->groupBy('semester_id')
            ->with('semester')
            ->get()
            ->sortByDesc(fn ($row) => $row->semester?->id);

        $builder = app(ResultApprovalGridBuilder::class);
        $groups = [];

        foreach ($pending as $row) {
            $semester = $row->semester;
            if (! $semester) {
                continue;
            }

            $programmeLevelPairs = Result::query()
                ->where('results.status', 'pending_approval')
                ->where('results.semester_id', $semester->id)
                ->join('courses', 'courses.id', '=', 'results.course_id')
                ->selectRaw('courses.programme_id, courses.nta_level')
                ->distinct()
                ->get();

            $grids = [];
            $summary = ['total' => 0, 'pass' => 0, 'fail' => 0];

            foreach ($programmeLevelPairs as $pair) {
                $programme = Programme::find($pair->programme_id);
                if (! $programme) {
                    continue;
                }

                $grid = $builder->build($semester, $programme, $pair->nta_level);
                if (empty($grid['rows'])) {
                    continue;
                }

                $grids[] = $grid;
                $summary['total'] += $grid['summary']['total'];
                $summary['pass'] += $grid['summary']['pass'];
                $summary['fail'] += $grid['summary']['fail'];
            }

            $groups[$row->semester_id] = ['grids' => $grids, 'summary' => $summary];
        }

        return view('results.approvals', compact('pending', 'canApprove', 'groups'));
    }

    public function approve(Request $request)
    {
        $this->ensureApproverRole();

        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'sms_template' => ['required', Rule::in([ResultReleaseSmsService::TEMPLATE_CA, ResultReleaseSmsService::TEMPLATE_FINAL])],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $count = Result::where('semester_id', $semester->id)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        if ($count > 0) {
            app(ResultReleaseSmsService::class)->notifySemester($semester, $validated['sms_template'], auth()->id());
        }

        ActivityLog::log('results.approved', null, null, "semester_id: {$semester->id}, rows: {$count}");

        return redirect()->route('results.approvals.index')->with('success', "Approved {$count} result row(s) for {$semester->label}.");
    }

    public function reject(Request $request)
    {
        $this->ensureApproverRole();

        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'review_notes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $count = Result::where('semester_id', $semester->id)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'review_notes' => $validated['review_notes'],
            ]);

        if ($count > 0) {
            User::query()
                ->whereIn('role', ['examination_officer', 'vice_principal_arc', 'hod_cmt', 'hod_mlt', 'administrator'])
                ->get()
                ->each
                ->notify(new ResultsRejectedNotification($semester, $validated['review_notes']));
        }

        ActivityLog::log('results.rejected', null, null, "semester_id: {$semester->id}, rows: {$count}, notes: {$validated['review_notes']}");

        return redirect()->route('results.approvals.index')->with('success', "Rejected {$count} result row(s) for {$semester->label}.");
    }
}

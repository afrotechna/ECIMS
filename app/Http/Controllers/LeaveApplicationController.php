<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\StaffLeaveDecisionNotification;
use App\Notifications\StaffLeavePendingNotification;
use App\Notifications\StaffLeaveSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveApplicationController extends Controller
{
    /**
     * Roles that may approve staff leave. Vice Principals act on behalf of the Principal
     * when the Principal is unavailable, rather than leaving approval blocked entirely.
     */
    private const APPROVER_ROLES = ['principal', 'vice_principal_afp', 'vice_principal_arc'];

    private function ensureStaffOnly(): void
    {
        if (auth()->user()->isStudent()) {
            abort(403);
        }
    }

    private function ensureApproverRole(): void
    {
        $role = User::normalizeRoleSlug((string) auth()->user()->role);
        if (! in_array($role, self::APPROVER_ROLES, true)) {
            abort(403, 'Only the Principal or a Vice Principal can approve or reject leave requests.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureStaffOnly();
        $query = LeaveApplication::with(['staffUser', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $applications = $query->orderByDesc('created_at')->paginate(20);

        $canApproveLeave = in_array(User::normalizeRoleSlug((string) auth()->user()->role), self::APPROVER_ROLES, true);

        return view('leave-applications.index', compact('applications', 'canApproveLeave'));
    }

    public function create()
    {
        $this->ensureStaffOnly();

        return view('leave-applications.create');
    }

    public function store(Request $request)
    {
        $this->ensureStaffOnly();
        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $days = Carbon::parse($validated['from_date'])->diffInDays(Carbon::parse($validated['to_date'])) + 1;
        if ($days < 14 || $days > 28) {
            return back()
                ->withInput()
                ->withErrors(['to_date' => 'Staff leave must be between 14 and 28 days.']);
        }

        $validated['student_id'] = null;
        $validated['staff_user_id'] = auth()->id();
        $validated['status'] = 'pending';
        $leave = LeaveApplication::create($validated);

        auth()->user()->notify(new StaffLeavePendingNotification($leave));
        User::query()
            ->whereIn('role', [...self::APPROVER_ROLES, 'administrator'])
            ->get()
            ->each
            ->notify(new StaffLeaveSubmittedNotification($leave));

        return redirect()->route('leave-applications.index')->with('success', 'Leave application submitted.');
    }

    public function approve(LeaveApplication $leave_application)
    {
        $this->ensureStaffOnly();
        $this->ensureApproverRole();

        $leave_application->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        if ($leave_application->staffUser) {
            $leave_application->staffUser->notify(new StaffLeaveDecisionNotification($leave_application->fresh()));
        }
        ActivityLog::log('leave.approved', LeaveApplication::class, $leave_application->id, "Leave approved for {$leave_application->staffUser?->name}");

        return redirect()->route('leave-applications.index')->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveApplication $leave_application)
    {
        $this->ensureStaffOnly();
        $this->ensureApproverRole();

        $leave_application->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->input('notes'),
        ]);
        if ($leave_application->staffUser) {
            $leave_application->staffUser->notify(new StaffLeaveDecisionNotification($leave_application->fresh()));
        }
        ActivityLog::log('leave.rejected', LeaveApplication::class, $leave_application->id, "Leave rejected for {$leave_application->staffUser?->name}");

        return redirect()->route('leave-applications.index')->with('success', 'Leave rejected.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\StaffLeaveDecisionNotification;
use App\Notifications\StaffLeavePendingNotification;
use App\Notifications\StaffLeaveSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveApplicationController extends Controller
{
    private function ensureStaffOnly(): void
    {
        if (auth()->user()->isStudent()) {
            abort(403);
        }
    }

    private function ensurePrincipalApproverOnly(): void
    {
        $role = User::normalizeRoleSlug((string) auth()->user()->role);
        if ($role !== 'principal') {
            abort(403, 'Only principal can approve or reject leave requests.');
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

        $canApproveLeave = User::normalizeRoleSlug((string) auth()->user()->role) === 'principal';

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
            ->where('role', 'principal')
            ->get()
            ->each
            ->notify(new StaffLeaveSubmittedNotification($leave));

        return redirect()->route('leave-applications.index')->with('success', 'Leave application submitted.');
    }

    public function approve(LeaveApplication $leave_application)
    {
        $this->ensureStaffOnly();
        $this->ensurePrincipalApproverOnly();

        $leave_application->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        if ($leave_application->staffUser) {
            $leave_application->staffUser->notify(new StaffLeaveDecisionNotification($leave_application->fresh()));
        }

        return redirect()->route('leave-applications.index')->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveApplication $leave_application)
    {
        $this->ensureStaffOnly();
        $this->ensurePrincipalApproverOnly();

        $leave_application->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->input('notes'),
        ]);
        if ($leave_application->staffUser) {
            $leave_application->staffUser->notify(new StaffLeaveDecisionNotification($leave_application->fresh()));
        }
        return redirect()->route('leave-applications.index')->with('success', 'Leave rejected.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date') ?? now()->toDateString();
        $staff = User::query()
            ->where('role', '!=', 'student')
            ->where('role', '!=', 'guardian')
            ->orderByRaw(User::staffRoleSortSqlCase())
            ->orderBy('surname')
            ->orderBy('name')
            ->get();

        $marks = StaffAttendance::query()
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('user_id');

        return view('staff-attendance.index', compact('staff', 'marks', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'records' => ['required', 'array'],
            'records.*.user_id' => ['required', 'exists:users,id'],
            'records.*.status' => ['required', 'string', 'in:'.implode(',', array_keys(StaffAttendance::STATUSES))],
            'records.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['records'] as $row) {
            StaffAttendance::updateOrCreate(
                [
                    'user_id' => $row['user_id'],
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'status' => $row['status'],
                    'notes' => $row['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        return redirect()
            ->route('staff-attendance.index', ['date' => $validated['attendance_date']])
            ->with('success', 'Staff attendance saved.');
    }
}

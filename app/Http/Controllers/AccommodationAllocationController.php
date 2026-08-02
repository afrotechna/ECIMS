<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAllocation;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class AccommodationAllocationController extends Controller
{
    public function index(Request $request)
    {
        $query = AccommodationAllocation::with(['student', 'room.hostel']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $allocations = $query->orderByDesc('from_date')->paginate(15);
        return view('accommodation-allocations.index', compact('allocations'));
    }

    public function create()
    {
        $students = Student::where('status', 'active')
            ->whereDoesntHave('accommodationAllocations', fn ($q) => $q->where('status', 'active'))
            ->whereHas('semesterRegistrations', fn ($q) => $q->where('status', 'approved')->whereNull('wizard_step'))
            ->orderBy('reg_no')
            ->get();
        $rooms = $this->roomsSelectableForAllocation(null);
        $roomOptionsUrl = route('accommodation-allocations.room-options');

        return view('accommodation-allocations.create', compact('students', 'rooms', 'roomOptionsUrl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'string', 'in:active,ended'],
        ]);
        $validated['status'] = $validated['status'] ?? 'active';

        $room = Room::findOrFail($validated['room_id']);
        if ($validated['status'] === 'active' && $room->activeAllocationsCount() >= $room->bed_count) {
            return redirect()->back()->withInput()->with('error', 'Room has no available berths (all beds are already allocated).');
        }

        try {
            DB::transaction(function () use ($validated) {
                $existing = AccommodationAllocation::query()
                    ->where('student_id', $validated['student_id'])
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->exists();
                if ($existing) {
                    throw new \RuntimeException('Student already has an active allocation. End it first.');
                }

                AccommodationAllocation::create($validated);
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            if ($this->isDuplicateActiveStudent($e)) {
                return redirect()->back()->withInput()->with('error', 'Student already has an active allocation. End it first.');
            }
            throw $e;
        }

        return redirect()->route('accommodation-allocations.index')->with('success', 'Allocation created successfully.');
    }

    public function edit(AccommodationAllocation $allocation)
    {
        $students = Student::query()
            ->where(function ($q) use ($allocation) {
                $q->whereDoesntHave('accommodationAllocations', fn ($q2) => $q2->where('status', 'active'))
                    ->orWhere('id', $allocation->student_id);
            })
            ->where(function ($q) use ($allocation) {
                $q->whereHas('semesterRegistrations', fn ($q2) => $q2->where('status', 'approved')->whereNull('wizard_step'))
                    ->orWhere('id', $allocation->student_id);
            })
            ->orderBy('reg_no')
            ->get();
        $rooms = $this->roomsSelectableForAllocation($allocation->room_id);
        $roomOptionsUrl = route('accommodation-allocations.room-options', ['allocation_id' => $allocation->id]);

        return view('accommodation-allocations.edit', compact('allocation', 'students', 'rooms', 'roomOptionsUrl'));
    }

    public function update(Request $request, AccommodationAllocation $allocation)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['required', 'string', 'in:active,ended'],
        ]);

        if ($validated['status'] === 'active') {
            $targetRoom = Room::findOrFail($validated['room_id']);
            $others = AccommodationAllocation::query()
                ->where('room_id', $targetRoom->id)
                ->where('status', 'active')
                ->where('id', '!=', $allocation->id)
                ->count();
            if ($others >= $targetRoom->bed_count) {
                return redirect()->back()->withInput()->with('error', 'Room has no available berths (all beds are already allocated).');
            }
        }

        $allocation->update($validated);
        return redirect()->route('accommodation-allocations.index')->with('success', 'Allocation updated.');
    }

    public function end(AccommodationAllocation $accommodation_allocation)
    {
        if ($accommodation_allocation->status === 'ended') {
            return redirect()->route('accommodation-allocations.index')->with('info', 'Allocation already ended.');
        }
        $accommodation_allocation->update(['to_date' => now(), 'status' => 'ended']);
        return redirect()->route('accommodation-allocations.index')->with('success', 'Allocation ended.');
    }

    /**
     * JSON list of rooms available for allocation (refreshed when the student changes on the form).
     */
    public function roomOptions(Request $request): JsonResponse
    {
        $alwaysInclude = null;
        if ($request->filled('allocation_id')) {
            $alwaysInclude = AccommodationAllocation::query()
                ->whereKey($request->integer('allocation_id'))
                ->value('room_id');
        }
        $rooms = $this->roomsSelectableForAllocation($alwaysInclude);

        return response()->json([
            'rooms' => $rooms->map(fn (Room $r) => [
                'id' => $r->id,
                'label' => $r->hostel->name.' — '.$r->name.' ('.$r->remainingBerths().' remaining)',
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Rooms with at least one free bed against active allocations, plus an optional room always included (edit).
     * Fully booked rooms are omitted so another student cannot pick the same room from the list.
     *
     * @return \Illuminate\Support\Collection<int, Room>
     */
    private function roomsSelectableForAllocation(?int $alwaysIncludeRoomId): \Illuminate\Support\Collection
    {
        $rooms = Room::query()
            ->with('hostel')
            ->where('is_active', true)
            ->withCount([
                'accommodationAllocations' => function ($q) {
                    $q->where('status', 'active');
                },
            ])
            ->orderBy('hostel_id')
            ->orderBy('name')
            ->get();

        return $rooms->filter(function (Room $r) use ($alwaysIncludeRoomId) {
            if ($alwaysIncludeRoomId !== null && (int) $r->id === $alwaysIncludeRoomId) {
                return true;
            }
            $occupied = (int) ($r->accommodation_allocations_count ?? 0);

            return $r->bed_count > $occupied;
        })->values();
    }

    private function isDuplicateActiveStudent(QueryException $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'active_student_key')
            || str_contains($msg, 'Duplicate entry');
    }
}

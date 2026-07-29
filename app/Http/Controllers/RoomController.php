<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $query = Room::with('hostel');
        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->hostel_id);
        }
        $ordered = $query
            ->orderBy('hostel_id')
            ->orderByRaw('block_number IS NULL, block_number')
            ->orderByRaw('room_in_block IS NULL, room_in_block')
            ->orderBy('name')
            ->get();

        $blocksPerPage = 5;
        $groups = $this->roomsGroupedByBlockPage($ordered);
        $totalGroups = count($groups);
        $currentPage = max(1, (int) $request->query('page', 1));
        $lastPage = max(1, (int) ceil($totalGroups / $blocksPerPage));
        if ($totalGroups === 0) {
            $currentPage = 1;
        } elseif ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }
        $offset = ($currentPage - 1) * $blocksPerPage;
        $pageGroups = array_slice($groups, $offset, $blocksPerPage);
        $items = collect($pageGroups)->flatten(1)->values();

        $rooms = new LengthAwarePaginator(
            $items,
            $totalGroups,
            $blocksPerPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ]
        );

        $hostels = Hostel::where('is_active', true)->orderBy('name')->get();
        $roomGroups = $this->groupRoomsByBlock($rooms->getCollection()->values()->all());

        return view('rooms.index', compact('rooms', 'hostels', 'roomGroups'));
    }

    /**
     * Live occupancy: students currently resident per room (by allocation dates + status).
     */
    public function occupancy(Request $request)
    {
        $hostels = Hostel::where('is_active', true)->orderBy('name')->get();
        $rooms = $this->roomsForOccupancy($request);
        $liveDataUrl = route('rooms.occupancy.live-data', $request->filled('hostel_id') ? ['hostel_id' => $request->hostel_id] : []);
        $roomGroups = $this->groupRoomsByBlock($rooms->values()->all());

        return view('rooms.occupancy', compact('rooms', 'hostels', 'liveDataUrl', 'roomGroups'));
    }

    public function occupancyLiveData(Request $request): JsonResponse
    {
        $rooms = $this->roomsForOccupancy($request);
        $payload = $rooms->map(function (Room $room) {
            $students = $room->effectiveAllocationsNow
                ->unique('student_id')
                ->values()
                ->map(fn ($a) => [
                    'id' => $a->student_id,
                    'full_name' => $a->student->full_name,
                    'reg_no' => $a->student->reg_no,
                    'phone' => $a->student->phone ? (string) $a->student->phone : '',
                    'programme' => $a->student->programme?->name ?? '—',
                ]);
            $occupied = $students->count();
            $vacant = max(0, $room->bed_count - $occupied);
            $fully = $room->bed_count > 0 && $occupied >= $room->bed_count;

            return [
                'id' => $room->id,
                'occupied' => $occupied,
                'vacant' => $vacant,
                'fully_occupied' => $fully,
                'students' => $students->values(),
            ];
        });

        return response()->json([
            'updated_at' => now()->toIso8601String(),
            'rooms' => $payload,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function create(Request $request)
    {
        $hostels = Hostel::where('is_active', true)->orderBy('name')->get();
        $preselectedHostelId = $request->get('hostel_id');
        $hostelLayouts = $hostels->map(fn (Hostel $h) => [
            'id' => $h->id,
            'blocks' => (int) $h->block_count,
            'roomsPerBlock' => (int) $h->rooms_per_block,
        ])->values();

        return view('rooms.create', compact('hostels', 'preselectedHostelId', 'hostelLayouts'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedRoom($request, null);

        Room::create($validated);

        return redirect()->route('rooms.index')->with('success', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        $hostels = Hostel::where('is_active', true)->orderBy('name')->get();
        $hostelLayouts = $hostels->map(fn (Hostel $h) => [
            'id' => $h->id,
            'blocks' => (int) $h->block_count,
            'roomsPerBlock' => (int) $h->rooms_per_block,
        ])->values();

        return view('rooms.edit', compact('room', 'hostels', 'hostelLayouts'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $this->validatedRoom($request, $room);
        $room->update($validated);

        return redirect()->route('rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        if ($room->accommodationAllocations()->where('status', 'active')->exists()) {
            return redirect()->route('rooms.index')->with('error', 'Cannot delete room with active allocations. End allocations first.');
        }
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Room::class,
            'rooms.index',
            fn (Request $r) => array_filter(['hostel_id' => $r->input('hostel_id', $r->query('hostel_id'))], fn ($v) => $v !== null && $v !== ''),
            singularLabel: 'room',
            deleter: function (Room $room) {
                if ($room->accommodationAllocations()->where('status', 'active')->exists()) {
                    return false;
                }
                $room->delete();

                return true;
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedRoom(Request $request, ?Room $room): array
    {
        $validated = $request->validate([
            'hostel_id' => ['required', 'exists:hostels,id'],
            'name' => ['nullable', 'string', 'max:50'],
            'bed_count' => ['required', 'integer', 'min:1', 'max:32'],
            'block_number' => ['nullable', 'integer', 'min:1', 'max:99', 'required_with:room_in_block'],
            'room_in_block' => ['nullable', 'integer', 'min:1', 'max:99', 'required_with:block_number'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $room === null
            ? $request->boolean('is_active', true)
            : $request->boolean('is_active');

        $hostel = Hostel::findOrFail($validated['hostel_id']);
        $bn = $validated['block_number'] ?? null;
        $rn = $validated['room_in_block'] ?? null;

        if ($bn !== null && $rn !== null) {
            $bn = (int) $bn;
            $rn = (int) $rn;
            if ($bn < 1 || $bn > (int) $hostel->block_count) {
                throw ValidationException::withMessages([
                    'block_number' => 'Choose a block from Block 1 to Block '.$hostel->block_count.' for this hostel.',
                ]);
            }
            if ($rn < 1 || $rn > (int) $hostel->rooms_per_block) {
                throw ValidationException::withMessages([
                    'room_in_block' => 'Choose a room from Room 1 to Room '.$hostel->rooms_per_block.' in each block.',
                ]);
            }
            $validated['block_number'] = $bn;
            $validated['room_in_block'] = $rn;
            $validated['name'] = Room::generatedCode($bn, $rn);

            $q = Room::query()
                ->where('hostel_id', $validated['hostel_id'])
                ->where('block_number', $bn)
                ->where('room_in_block', $rn);
            if ($room !== null) {
                $q->where('id', '!=', $room->id);
            }
            if ($q->exists()) {
                throw ValidationException::withMessages([
                    'block_number' => 'This block and room is already registered in the selected hostel.',
                ]);
            }
        } else {
            $validated['block_number'] = null;
            $validated['room_in_block'] = null;
            $name = trim((string) ($validated['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'block_number' => 'Select a block and room (room code is generated automatically), or enter a custom room name only.',
                ]);
            }
            $validated['name'] = $name;
        }

        return $validated;
    }

    /**
     * Rooms for the occupancy board, with residents loaded for “today”.
     *
     * @return Collection<int, Room>
     */
    private function roomsForOccupancy(Request $request): Collection
    {
        $query = Room::query()
            ->with(['hostel', 'effectiveAllocationsNow.student.programme'])
            ->where('is_active', true)
            ->orderBy('hostel_id')
            ->orderByRaw('block_number IS NULL, block_number')
            ->orderByRaw('room_in_block IS NULL, room_in_block')
            ->orderBy('name');

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->integer('hostel_id'));
        }

        return $query->get();
    }

    /**
     * Split ordered rooms into groups: one group per (hostel, block_number), or one group per room when block_number is null.
     *
     * @param  Collection<int, Room>  $rooms
     * @return list<list<Room>>
     */
    private function roomsGroupedByBlockPage(Collection $rooms): array
    {
        $groups = [];
        $currentKey = null;
        $bucket = [];
        foreach ($rooms as $room) {
            $key = $room->block_number !== null
                ? $room->hostel_id.'-b'.$room->block_number
                : $room->hostel_id.'-r'.$room->id;
            if ($key !== $currentKey) {
                if ($bucket !== []) {
                    $groups[] = $bucket;
                }
                $bucket = [$room];
                $currentKey = $key;
            } else {
                $bucket[] = $room;
            }
        }
        if ($bucket !== []) {
            $groups[] = $bucket;
        }

        return $groups;
    }

    /**
     * Group ordered rooms into per-block sections for the collapsible table:
     * consecutive rooms sharing (hostel, block_number) become one group;
     * rooms without a block_number each form their own single-room group.
     *
     * @param  list<Room>  $items
     * @return list<array{key: string, hostel: Hostel|null, blockNumber: int|null, rooms: list<Room>}>
     */
    private function groupRoomsByBlock(array $items): array
    {
        $groups = [];
        $currentKey = null;
        foreach ($items as $room) {
            /** @var Room $room */
            $key = $room->block_number !== null
                ? 'b'.$room->hostel_id.'-'.$room->block_number
                : 'r'.$room->id;
            if ($key !== $currentKey) {
                $groups[] = [
                    'key' => $key,
                    'hostel' => $room->hostel,
                    'blockNumber' => $room->block_number,
                    'rooms' => [],
                ];
                $currentKey = $key;
            }
            $groups[array_key_last($groups)]['rooms'][] = $room;
        }

        return $groups;
    }
}

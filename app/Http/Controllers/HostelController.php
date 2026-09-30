<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Hostel;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HostelController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $hostels = Hostel::query()
            ->withCount('rooms')
            ->withSum('rooms', 'bed_count')
            ->orderBy('name')
            ->paginate(10);

        return view('hostels.index', compact('hostels'));
    }

    public function create()
    {
        return view('hostels.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedLayout($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        $hostel = Hostel::create($validated);

        if ($request->boolean('generate_rooms')) {
            try {
                $this->generateStandardRooms($hostel->fresh());
            } catch (\RuntimeException $e) {
                return redirect()
                    ->route('hostels.edit', $hostel)
                    ->with('warning', 'Hostel saved, but rooms were not created: '.$e->getMessage());
            }

            $n = $hostel->fresh()->rooms()->count();

            return redirect()->route('hostels.index')->with('success', 'Hostel created with '.$n.' rooms ('.$validated['block_count'].' blocks × '.$validated['rooms_per_block'].' rooms, '.$validated['beds_per_room'].' berths per room).');
        }

        return redirect()->route('hostels.index')->with('success', 'Hostel created successfully. Edit the hostel and use “Generate standard rooms” to add all rooms when ready.');
    }

    public function edit(Hostel $hostel)
    {
        $hostel->loadCount('rooms');

        return view('hostels.edit', compact('hostel'));
    }

    public function update(Request $request, Hostel $hostel)
    {
        $validated = $this->validatedLayout($request);
        $validated['is_active'] = $request->boolean('is_active');

        $hostel->update($validated);

        return redirect()->route('hostels.index')->with('success', 'Hostel updated successfully.');
    }

    /**
     * Create all rooms from the hostel’s block × room layout (only when the hostel has no rooms yet).
     */
    public function generateRooms(Hostel $hostel)
    {
        try {
            $this->generateStandardRooms($hostel->fresh());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('hostels.edit', $hostel)
                ->with('error', $e->getMessage());
        }

        $n = $hostel->fresh()->rooms()->count();

        return redirect()
            ->route('hostels.edit', $hostel)
            ->with('success', 'Created '.$n.' rooms from the configured layout.');
    }

    public function destroy(Hostel $hostel)
    {
        if ($hostel->rooms()->exists()) {
            return redirect()->route('hostels.index')->with('error', 'Cannot delete hostel with rooms. Remove rooms first.');
        }
        $hostel->delete();

        return redirect()->route('hostels.index')->with('success', 'Hostel deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Hostel::class,
            'hostels.index',
            singularLabel: 'hostel',
            deleter: function (Hostel $hostel) {
                if ($hostel->rooms()->exists()) {
                    return false;
                }
                $hostel->delete();

                return true;
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLayout(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female'],
            'block_count' => ['required', 'integer', 'min:1', 'max:60'],
            'rooms_per_block' => ['required', 'integer', 'min:1', 'max:30'],
            'beds_per_room' => ['required', 'integer', 'min:1', 'max:32'],
        ]);
    }

    private function generateStandardRooms(Hostel $hostel): void
    {
        if ($hostel->rooms()->exists()) {
            throw new \RuntimeException('This hostel already has rooms. Delete existing rooms first (only if they have no active allocations), then generate again.');
        }

        $blocks = (int) $hostel->block_count;
        $roomsPerBlock = (int) $hostel->rooms_per_block;
        $beds = (int) $hostel->beds_per_room;

        if ($blocks < 1 || $roomsPerBlock < 1 || $beds < 1) {
            throw new \RuntimeException('Set blocks, rooms per block, and beds per room on the hostel before generating.');
        }

        DB::transaction(function () use ($hostel, $blocks, $roomsPerBlock, $beds) {
            for ($b = 1; $b <= $blocks; $b++) {
                for ($r = 1; $r <= $roomsPerBlock; $r++) {
                    Room::create([
                        'hostel_id' => $hostel->id,
                        'block_number' => $b,
                        'room_in_block' => $r,
                        'name' => Room::generatedCode($b, $r),
                        'bed_count' => $beds,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}

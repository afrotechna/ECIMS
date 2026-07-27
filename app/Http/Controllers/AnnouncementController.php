<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Announcement;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $announcements = Announcement::with('creator')->orderByDesc('created_at')->paginate(15);

        return view('announcements.index', compact('announcements'));
    }

    public function create()
    {
        $programmes = Programme::where('is_active', true)->orderBy('name')->get();

        return view('announcements.create', compact('programmes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAnnouncement($request);
        $validated['created_by'] = auth()->id();
        $announcement = Announcement::create($validated);
        if (Schema::hasTable('activity_log')) {
            \App\Models\ActivityLog::log('announcement.created', Announcement::class, $announcement->id, $validated['title']);
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement)
    {
        $programmes = Programme::where('is_active', true)->orderBy('name')->get();

        return view('announcements.edit', compact('announcement', 'programmes'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validatedAnnouncement($request));

        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();
        if (Schema::hasTable('activity_log')) {
            \App\Models\ActivityLog::log('announcement.deleted', null, null, $title);
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords($request, Announcement::class, 'announcements.index', singularLabel: 'announcement');
    }

    /** @return array<string, mixed> */
    private function validatedAnnouncement(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'show_until' => ['nullable', 'date'],
            'audience' => ['required', 'string', 'in:'.implode(',', array_keys(Announcement::AUDIENCES))],
            'target_nta_levels' => ['nullable', 'array'],
            'target_nta_levels.*' => ['integer', 'in:4,5,6'],
            'target_programme_ids' => ['nullable', 'array'],
            'target_programme_ids.*' => ['integer', 'exists:programmes,id'],
            'target_programme_all' => ['nullable', 'boolean'],
        ]);

        $levels = array_values(array_unique(array_map('intval', $validated['target_nta_levels'] ?? [])));
        $validated['target_nta_levels'] = $levels === [] ? null : $levels;

        $programmes = array_values(array_unique(array_map('intval', $validated['target_programme_ids'] ?? [])));
        if ($request->boolean('target_programme_all')) {
            $programmes = [];
        }
        $validated['target_programme_ids'] = $programmes === [] ? null : $programmes;
        unset($validated['target_programme_all']);

        return $validated;
    }
}

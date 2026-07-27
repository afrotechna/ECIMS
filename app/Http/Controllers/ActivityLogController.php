<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $hasTable = Schema::hasTable('activity_log');
        if (! $hasTable) {
            $entries = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
            return view('activity-log.index', ['entries' => $entries, 'users' => collect(), 'actions' => collect(), 'hasActivityLog' => false]);
        }

        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $entries = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values();

        return view('activity-log.index', compact('entries', 'users', 'actions') + ['hasActivityLog' => true]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! Schema::hasTable('activity_log')) {
            abort(404);
        }

        $query = ActivityLog::with('user')->orderByDesc('created_at');
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        $entries = $query->limit(10000)->get();

        $filename = 'activity-log-' . date('Y-m-d-His') . '.csv';
        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User', 'Action', 'Subject Type', 'Subject ID', 'Description', 'IP']);
            foreach ($entries as $e) {
                fputcsv($out, [
                    $e->created_at->format('Y-m-d H:i:s'),
                    $e->user ? $e->user->email : '',
                    $e->action,
                    $e->subject_type ?? '',
                    $e->subject_id ?? '',
                    $e->description ?? '',
                    $e->ip ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

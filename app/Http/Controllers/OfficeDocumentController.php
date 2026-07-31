<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OfficeDocument;
use App\Models\User;
use App\Notifications\OfficeDocumentReceivedNotification;
use App\Notifications\OfficeDocumentStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OfficeDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab', 'inbox')->toString();
        $tab = in_array($tab, ['inbox', 'sent'], true) ? $tab : 'inbox';

        $query = OfficeDocument::with(['sender', 'recipient'])->orderByDesc('created_at');
        $query = $tab === 'sent'
            ? $query->where('sender_id', auth()->id())
            : $query->where('recipient_id', auth()->id());

        $documents = $query->paginate(20)->withQueryString();

        return view('office-documents.index', compact('documents', 'tab'));
    }

    public function create(Request $request): View
    {
        $replyTo = null;
        if ($request->filled('reply_to')) {
            $replyTo = OfficeDocument::findOrFail($request->integer('reply_to'));
            $this->authorizeParty($replyTo);
        }

        $recipients = User::query()
            ->whereNotIn('role', ['student', 'guardian'])
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        return view('office-documents.create', compact('recipients', 'replyTo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id', \Illuminate\Validation\Rule::notIn([auth()->id()])],
            'parent_id' => ['nullable', 'exists:office_documents,id'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'document' => ['required', 'file', 'max:20480'],
        ]);

        if ($validated['parent_id'] ?? null) {
            $this->authorizeParty(OfficeDocument::findOrFail($validated['parent_id']));
        }

        $file = $request->file('document');
        $path = $file->store('office-documents/'.date('Y'), 'local');

        $document = OfficeDocument::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $validated['recipient_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'sent',
        ]);

        ActivityLog::log('office_document.sent', OfficeDocument::class, $document->id, "\"{$document->title}\" sent to ".($document->recipient->name ?? ''));

        $document->recipient?->notify(new OfficeDocumentReceivedNotification($document));

        return redirect()->route('office-documents.show', $document)->with('success', 'Document sent.');
    }

    public function show(OfficeDocument $office_document): View
    {
        $this->authorizeParty($office_document);
        $this->markReceivedOnFirstView($office_document);
        $office_document->load(['sender', 'recipient', 'parent', 'replies.sender', 'replies.recipient']);

        return view('office-documents.show', ['document' => $office_document]);
    }

    public function download(OfficeDocument $office_document)
    {
        $this->authorizeParty($office_document);
        $this->markReceivedOnFirstView($office_document);

        if (! Storage::disk('local')->exists($office_document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($office_document->file_path, $office_document->original_name);
    }

    /** The moment the recipient opens the document (via notification link or the show page) or downloads it, mark it Received. */
    private function markReceivedOnFirstView(OfficeDocument $document): void
    {
        if (auth()->id() === $document->recipient_id && $document->received_at === null) {
            $document->update(['status' => 'received', 'received_at' => now()]);
            ActivityLog::log('office_document.received', OfficeDocument::class, $document->id, "\"{$document->title}\" received by ".auth()->user()->name);
            $document->sender?->notify(new OfficeDocumentStatusNotification($document->fresh()));
        }
    }

    public function markPrinted(OfficeDocument $office_document): RedirectResponse
    {
        $this->authorizeRecipient($office_document);
        abort_unless($office_document->received_at !== null, 422, 'Mark the document as received before marking it printed.');

        if ($office_document->printed_at === null) {
            $office_document->update(['status' => 'printed', 'printed_at' => now()]);
            ActivityLog::log('office_document.printed', OfficeDocument::class, $office_document->id, "\"{$office_document->title}\" printed by ".auth()->user()->name);
            $office_document->sender?->notify(new OfficeDocumentStatusNotification($office_document->fresh()));
        }

        return back()->with('success', 'Marked as printed.');
    }

    public function markCompleted(OfficeDocument $office_document): RedirectResponse
    {
        $this->authorizeRecipient($office_document);
        abort_unless($office_document->printed_at !== null, 422, 'Mark the document as printed before marking it completed.');

        if ($office_document->completed_at === null) {
            $office_document->update(['status' => 'completed', 'completed_at' => now()]);
            ActivityLog::log('office_document.completed', OfficeDocument::class, $office_document->id, "\"{$office_document->title}\" completed by ".auth()->user()->name);
            $office_document->sender?->notify(new OfficeDocumentStatusNotification($office_document->fresh()));
        }

        return back()->with('success', 'Marked as completed.');
    }

    private function authorizeParty(OfficeDocument $document): void
    {
        $userId = auth()->id();
        if ($document->sender_id !== $userId && $document->recipient_id !== $userId && ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    private function authorizeRecipient(OfficeDocument $document): void
    {
        if ($document->recipient_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Only the recipient can update this document\'s status.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\CertificateCollection;
use App\Models\ClinicalRotationRound;
use App\Models\Course;
use App\Models\ExamPaper;
use App\Models\ExamSlot;
use App\Models\FeeStructure;
use App\Models\Hostel;
use App\Models\InstitutionDocument;
use App\Models\InventoryItem;
use App\Models\PaymentInstalment;
use App\Models\Programme;
use App\Models\ProgrammeNtaLevelDocument;
use App\Models\Room;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Models\UserModulePermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TrashController extends Controller
{
    /** @return array<string, array{model: class-string<Model>, label: string, display: callable(Model): string}> */
    private function types(): array
    {
        return [
            'students' => ['model' => Student::class, 'label' => 'Students', 'display' => fn (Student $m) => $m->reg_no.' — '.$m->full_name],
            'programmes' => ['model' => Programme::class, 'label' => 'Programmes', 'display' => fn (Programme $m) => $m->code.' — '.$m->name],
            'courses' => ['model' => Course::class, 'label' => 'Modules', 'display' => fn (Course $m) => $m->code.' — '.$m->name],
            'semesters' => ['model' => Semester::class, 'label' => 'Semesters', 'display' => fn (Semester $m) => ($m->name ?: $m->academic_year.' Semester '.$m->number)],
            'fee_structures' => ['model' => FeeStructure::class, 'label' => 'Fee schedules', 'display' => fn (FeeStructure $m) => $m->academic_year.' — '.($m->programme?->code ?? 'All programmes')],
            'exam_slots' => ['model' => ExamSlot::class, 'label' => 'Exam slots', 'display' => fn (ExamSlot $m) => 'Exam slot #'.$m->id.' ('.$m->exam_date?->format('d M Y').')'],
            'timetable_slots' => ['model' => TimetableSlot::class, 'label' => 'Timetable slots', 'display' => fn (TimetableSlot $m) => 'Slot #'.$m->id],
            'clinical_rotation_rounds' => ['model' => ClinicalRotationRound::class, 'label' => 'Clinical rotation rounds', 'display' => fn (ClinicalRotationRound $m) => $m->title ?: ('Round #'.$m->id)],
            'exam_papers' => ['model' => ExamPaper::class, 'label' => 'Exam papers', 'display' => fn (ExamPaper $m) => $m->title ?: ('Paper #'.$m->id)],
            'payment_instalments' => ['model' => PaymentInstalment::class, 'label' => 'Payment instalments', 'display' => fn (PaymentInstalment $m) => ($m->label ?: 'Instalment #'.$m->id).' — '.($m->student->reg_no ?? '')],
            'users' => ['model' => User::class, 'label' => 'Guardian accounts', 'display' => fn (User $m) => $m->name.' ('.$m->email.')'],
            'student_documents' => ['model' => StudentDocument::class, 'label' => 'Student documents', 'display' => fn (StudentDocument $m) => $m->name.' — '.($m->student->reg_no ?? '')],
            'certificate_collections' => ['model' => CertificateCollection::class, 'label' => 'Certificate collections', 'display' => fn (CertificateCollection $m) => $m->certificate_number ?: ('#'.$m->id)],
            'announcements' => ['model' => Announcement::class, 'label' => 'Announcements', 'display' => fn (Announcement $m) => $m->title],
            'hostels' => ['model' => Hostel::class, 'label' => 'Hostels', 'display' => fn (Hostel $m) => $m->name],
            'rooms' => ['model' => Room::class, 'label' => 'Rooms', 'display' => fn (Room $m) => $m->name ?: ('Room #'.$m->id)],
            'inventory_items' => ['model' => InventoryItem::class, 'label' => 'Inventory items', 'display' => fn (InventoryItem $m) => $m->name],
            'user_module_permissions' => ['model' => UserModulePermission::class, 'label' => 'Permission grants', 'display' => fn (UserModulePermission $m) => ($m->user->name ?? '?').' — '.$m->module],
            'calendar_events' => ['model' => CalendarEvent::class, 'label' => 'Calendar events', 'display' => fn (CalendarEvent $m) => $m->title],
            'institution_documents' => ['model' => InstitutionDocument::class, 'label' => 'Institution documents', 'display' => fn (InstitutionDocument $m) => $m->title],
            'programme_nta_level_documents' => ['model' => ProgrammeNtaLevelDocument::class, 'label' => 'Programme NTA-level documents', 'display' => fn (ProgrammeNtaLevelDocument $m) => $m->document_type.' — '.($m->programme?->code ?? '')],
        ];
    }

    public function index(Request $request): View
    {
        $types = $this->types();
        $type = $request->string('type', 'students')->toString();
        if (! array_key_exists($type, $types)) {
            $type = 'students';
        }

        $counts = [];
        foreach ($types as $key => $config) {
            $counts[$key] = $config['model']::onlyTrashed()->count();
        }

        $records = $types[$type]['model']::onlyTrashed()
            ->with('deletedBy')
            ->orderByDesc('deleted_at')
            ->paginate(25)
            ->withQueryString();

        return view('trash.index', [
            'types' => $types,
            'type' => $type,
            'counts' => $counts,
            'records' => $records,
            'display' => $types[$type]['display'],
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $config = $this->typeOrAbort($type);
        $record = $config['model']::onlyTrashed()->findOrFail($id);

        try {
            $record->restore();
        } catch (QueryException $e) {
            return back()->with('error', 'Could not restore: a record with the same unique value already exists.');
        }

        ActivityLog::log('trash.restored', $config['model'], $record->id, "Restored {$config['label']} #{$record->id} from Trash");

        return back()->with('success', 'Restored from Trash.');
    }

    public function forceDelete(string $type, int $id): RedirectResponse
    {
        $config = $this->typeOrAbort($type);
        $record = $config['model']::onlyTrashed()->findOrFail($id);

        $this->purgeFileIfAny($record);

        ActivityLog::log('trash.purged', $config['model'], $record->id, "Permanently deleted {$config['label']} #{$record->id}");
        $record->forceDelete();

        return back()->with('success', 'Permanently deleted.');
    }

    /** @return array{model: class-string<Model>, label: string, display: callable(Model): string} */
    private function typeOrAbort(string $type): array
    {
        $types = $this->types();
        abort_unless(array_key_exists($type, $types), 404);

        return $types[$type];
    }

    private function purgeFileIfAny(Model $record): void
    {
        if ($record instanceof StudentDocument && Storage::disk('local')->exists($record->path)) {
            Storage::disk('local')->delete($record->path);
        }
        if ($record instanceof InstitutionDocument && Storage::disk('local')->exists($record->file_path)) {
            Storage::disk('local')->delete($record->file_path);
        }
    }
}

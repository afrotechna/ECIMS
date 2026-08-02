<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FeeStructure;
use App\Support\AcademicSession;
use App\Models\GraduationClearance;
use App\Models\LeaveApplication;
use App\Models\Payment;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function enrollment(Request $request)
    {
        $by = $request->get('by', 'programme');
        $intakeYear = $request->get('intake_year');
        $query = Student::with('programme')->where('status', 'active');
        if ($intakeYear) {
            $query->where('intake_year', $intakeYear);
        }
        if ($by === 'programme') {
            $data = $query->get()->groupBy('programme_id')->map(function ($students) {
                $p = $students->first()->programme;

                return ['name' => $p ? $p->name.' ('.$p->code.')' : 'N/A', 'count' => $students->count()];
            });
        } else {
            $data = $query->get()->groupBy('intake_year')->map(function ($students, $year) {
                return ['name' => 'Intake '.$year, 'count' => $students->count()];
            })->sortKeysDesc();
        }
        $intakeYears = Student::distinct()->pluck('intake_year')->sort()->values();

        return view('reports.enrollment', compact('data', 'by', 'intakeYear', 'intakeYears'));
    }

    public function enrollmentExport(Request $request): StreamedResponse
    {
        $by = $request->get('by', 'programme');
        $intakeYear = $request->get('intake_year');
        $query = Student::with('programme')->where('status', 'active');
        if ($intakeYear) {
            $query->where('intake_year', $intakeYear);
        }
        if ($by === 'programme') {
            $data = $query->get()->groupBy('programme_id')->map(function ($students) {
                $p = $students->first()->programme;

                return ['name' => $p ? $p->name.' ('.$p->code.')' : 'N/A', 'count' => $students->count()];
            });
        } else {
            $data = $query->get()->groupBy('intake_year')->map(function ($students, $year) {
                return ['name' => 'Intake '.$year, 'count' => $students->count()];
            })->sortKeysDesc();
        }
        $filename = 'enrollment-report-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Count']);
            foreach ($data as $row) {
                fputcsv($out, [$row['name'], $row['count']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function arrears(Request $request)
    {
        $students = $this->studentsWithArrears();
        $totalArrears = $students->sum('balance');

        return view('reports.arrears', compact('students', 'totalArrears'));
    }

    public function arrearsExport(Request $request): StreamedResponse
    {
        $students = $this->studentsWithArrears();
        $filename = 'arrears-report-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reg No', 'NACTVET No', 'Name', 'Programme', 'Balance (TZS)']);
            foreach ($students as $s) {
                fputcsv($out, [$s->reg_no, $s->nactvet_reg_no, $s->full_name, $s->programme->code ?? '', $s->balance]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function income(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $range = [$from.' 00:00:00', $to.' 23:59:59'];
        $baseQuery = Payment::query()->whereBetween('paid_at', $range)->whereHas('student');
        $total = (float) (clone $baseQuery)->sum('amount');
        $paymentCount = (int) (clone $baseQuery)->count();
        $byMethod = (clone $baseQuery)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
        $payments = Payment::query()
            ->with(['student.programme', 'receiver'])
            ->whereBetween('paid_at', $range)
            ->whereHas('student')
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.income', compact('total', 'paymentCount', 'byMethod', 'payments', 'from', 'to'));
    }

    public function incomeExport(Request $request): StreamedResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $payments = Payment::query()->with('student')->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])->whereHas('student')->orderByDesc('paid_at')->get();
        $filename = 'income-report-'.$from.'-to-'.$to.'.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Reg No', 'Student', 'Amount (TZS)', 'Method', 'Reference']);
            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->paid_at->format('Y-m-d H:i'),
                    $p->student->reg_no ?? '',
                    $p->student->full_name ?? '',
                    $p->amount,
                    $p->payment_method,
                    $p->reference ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function paymentByProgramme(Request $request)
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $payments = Payment::query()->with('student.programme')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->whereHas('student')
            ->get();
        $data = $payments->groupBy(fn ($p) => $p->student->programme_id ?? 0)->map(function ($items, $progId) {
            $prog = $items->first()->student->programme ?? null;

            return [
                'name' => $prog ? $prog->name.' ('.$prog->code.')' : 'N/A',
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        })->sortByDesc('total');

        $grandTotal = (float) $data->sum('total');
        $grandCount = (int) $data->sum('count');

        return view('reports.payment-by-programme', compact('data', 'from', 'to', 'grandTotal', 'grandCount'));
    }

    public function classList(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $courseId = $request->get('course_id');
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $courses = collect();
        $students = collect();
        $semester = null;
        $course = null;
        if ($semesterId) {
            $semester = Semester::find($semesterId);
            $courses = Course::whereHas('semesters', fn ($q) => $q->where('semesters.id', $semesterId))
                ->with('programme')->orderBy('programme_id')->orderBy('code')->get();
        }
        if ($semesterId && $courseId) {
            $course = Course::with('programme')->find($courseId);
            if ($course && $semester) {
                $students = Student::where('programme_id', $course->programme_id)
                    ->whereHas('semesterRegistrations', fn ($q) => $q->where('semester_id', $semesterId)->where('status', 'approved'))
                    ->with('programme')->orderBy('reg_no')->get();
            }
        }

        return view('reports.class-list', compact('semesters', 'courses', 'students', 'semester', 'course', 'semesterId', 'courseId'));
    }

    public function classListExport(Request $request): StreamedResponse
    {
        $semesterId = $request->get('semester_id');
        $courseId = $request->get('course_id');
        if (! $semesterId || ! $courseId) {
            abort(400, 'Semester and course are required.');
        }
        $course = Course::with('programme')->findOrFail($courseId);
        $students = Student::where('programme_id', $course->programme_id)
            ->whereHas('semesterRegistrations', fn ($q) => $q->where('semester_id', $semesterId)->where('status', 'approved'))
            ->with('programme')->orderBy('reg_no')->get();
        $semester = Semester::find($semesterId);
        $filename = 'class-list-'.($semester->name ?? 'sem').'-'.($course->code ?? 'course').'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reg No', 'NACTVET No', 'Name', 'Programme']);
            foreach ($students as $s) {
                fputcsv($out, [$s->reg_no, $s->nactvet_reg_no, $s->full_name, $s->programme->code ?? '']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function feeCollectionSummary(Request $request)
    {
        $academicYear = $request->get('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $feeStructures = FeeStructure::with('programme')->where('academic_year', $academicYear)->where('is_active', true)->get();
        $studentCounts = Student::where('status', 'active')->selectRaw('programme_id, count(*) as cnt')->groupBy('programme_id')->pluck('cnt', 'programme_id');
        $collected = Payment::query()->with('student')
            ->whereYear('paid_at', $academicYear)
            ->whereHas('student')
            ->get()
            ->groupBy(fn ($p) => $p->student->programme_id ?? 0)
            ->map(fn ($items) => $items->sum('amount'));
        $data = $feeStructures->map(function ($fs) use ($studentCounts, $collected) {
            $expected = ($fs->total ?? 0) * ($studentCounts->get($fs->programme_id, 0));
            $total = $collected->get($fs->programme_id, 0);

            return [
                'programme' => $fs->programme ? $fs->programme->name.' ('.$fs->programme->code.')' : 'N/A',
                'expected' => $expected,
                'collected' => $total,
                'students' => $studentCounts->get($fs->programme_id, 0),
            ];
        });
        $years = FeeStructure::distinct()->pluck('academic_year')->filter()->sort()->values();
        if ($years->isEmpty()) {
            $current = AcademicSession::currentStartYear();
            $years = collect([$current, $current - 1]);
        }
        $yearOptions = $years->mapWithKeys(fn ($y) => [(int) $y => AcademicSession::label((int) $y)])->all();

        $summaryTotals = [
            'expected' => (float) $data->sum('expected'),
            'collected' => (float) $data->sum('collected'),
            'students' => (int) $data->sum('students'),
            'rate' => 0.0,
        ];
        if ($summaryTotals['expected'] > 0) {
            $summaryTotals['rate'] = round(100 * $summaryTotals['collected'] / $summaryTotals['expected'], 1);
        }

        return view('reports.fee-collection-summary', compact('data', 'academicYear', 'years', 'yearOptions', 'summaryTotals'));
    }

    /**
     * Students Admission Control Sheet – report and export matching the official control sheet columns.
     */
    public function admissionControlSheet(Request $request)
    {
        $academicYear = (int) $request->get('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $programmeId = $request->get('programme_id');
        $intakeYear = $request->get('intake_year');
        $semesterNumber = (int) $request->get('semester', 1);
        if (! in_array($semesterNumber, [1, 2], true)) {
            $semesterNumber = 1;
        }
        $ntaLevel = $request->get('nta_level');

        $query = Student::with(['programme', 'payments', 'accommodationAllocations' => fn ($q) => $q->where('status', 'active')->with('room.hostel')])
            ->where('status', 'active')
            ->orderBy('programme_id')
            ->orderBy('reg_no');

        if ($programmeId) {
            $query->where('programme_id', $programmeId);
        }
        if ($intakeYear) {
            $query->where('intake_year', $intakeYear);
        }
        if ($ntaLevel !== null && $ntaLevel !== '') {
            $query->where('nta_level', (int) $ntaLevel);
        }

        $students = $query->get();
        $feeStructures = FeeStructure::with('feeStructureSemesters')
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->get();
        $programmes = \App\Models\Programme::orderBy('code')->get();

        $rows = $students->map(fn (Student $student, int $index) => $this->buildAdmissionControlRow($student, $feeStructures, $index + 1, $semesterNumber));

        $sheetTotals = [
            'expected_tuition' => (int) $rows->sum(fn ($r) => $r['expected_tuition']),
            'tuition_paid' => (int) $rows->sum(fn ($r) => $r['tuition_paid']),
        ];

        $intakeYears = Student::distinct()->pluck('intake_year')->filter()->sort()->values();

        return view('reports.admission-control-sheet', compact('rows', 'academicYear', 'programmes', 'programmeId', 'intakeYear', 'intakeYears', 'sheetTotals', 'semesterNumber', 'ntaLevel'));
    }

    /**
     * Single-student admission control row (e.g. after completing the semester registration wizard).
     */
    public function admissionControlSheetStudent(Request $request, Student $student)
    {
        if (! auth()->user()->canAccessAcademics() && ! auth()->user()->canAccessFinance()) {
            abort(403);
        }
        $academicYear = (int) $request->get('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $semesterNumber = (int) $request->get('semester', 1);
        if (! in_array($semesterNumber, [1, 2], true)) {
            $semesterNumber = 1;
        }
        $student->load(['programme', 'payments', 'accommodationAllocations' => fn ($q) => $q->where('status', 'active')->with('room.hostel')]);
        $feeStructures = FeeStructure::with('feeStructureSemesters')
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->get();
        $row = $this->buildAdmissionControlRow($student, $feeStructures, 1, $semesterNumber);

        return view('reports.admission-control-student', compact('student', 'row', 'academicYear', 'semesterNumber'));
    }

    public function admissionControlSheetExport(Request $request): StreamedResponse
    {
        $academicYear = (int) $request->get('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $programmeId = $request->get('programme_id');
        $intakeYear = $request->get('intake_year');
        $semesterNumber = (int) $request->get('semester', 1);
        if (! in_array($semesterNumber, [1, 2], true)) {
            $semesterNumber = 1;
        }
        $ntaLevel = $request->get('nta_level');

        $query = Student::with(['programme', 'payments', 'accommodationAllocations' => fn ($q) => $q->where('status', 'active')->with('room.hostel')])
            ->where('status', 'active')
            ->orderBy('programme_id')
            ->orderBy('reg_no');
        if ($programmeId) {
            $query->where('programme_id', $programmeId);
        }
        if ($intakeYear) {
            $query->where('intake_year', $intakeYear);
        }
        if ($ntaLevel !== null && $ntaLevel !== '') {
            $query->where('nta_level', (int) $ntaLevel);
        }
        $students = $query->get();
        $feeStructures = FeeStructure::with('feeStructureSemesters')
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->get();

        $headers = [
            'S/N',
            'NAME (FIRST, MIDDLE & SURNAME)',
            'GENDER',
            'NACTVET / FORM IV REGISTRATION NO.',
            'NTA LEVEL',
            'PROGRAMME OF STUDY',
            'YEAR OF STUDY',
            'REPORTING STATUS',
            'REPORTING DATE',
            'TUITION FEE STATUS',
            'EXPECTED TUITION (TZS) — semester '.$semesterNumber,
            'TUITION PAID (TZS)',
            'TUITION CONTROL NUMBER',
            'WHEN WILL COMPLETE TUITION FEE? (Specify date & Submit commitment letter)',
            'NHIF FEE (COLLEGE)',
            'NHIF PAID (TZS)',
            'NHIF STATUS',
            'NACTVET QA FEE (TZS)',
            'NACTVET QA PAID (TZS)',
            'NACTVET QA STATUS',
            'NACTVET QA CONTROL NUMBER',
            'JOINING INSTRUCTION NON ACADEMIC REQUIREMENTS SUBMITTED',
            'CERTIFICATES SUBMITTED',
            'HOSTEL ALLOCATION (BLOCK NO.)',
            'CLASS',
            'CLASS PROPERTY RECEIVED',
            'CHAIR NUMBER',
            'TABLE NUMBER',
        ];

        $filename = 'students-admission-control-sheet-'.$academicYear.'-sem'.$semesterNumber.'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students, $feeStructures, $headers, $academicYear, $semesterNumber) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['STUDENTS ADMISSION CONTROL SHEET']);
            fputcsv($out, ['Academic year (fee schedule): '.$academicYear.' · Semester '.$semesterNumber]);
            fputcsv($out, ['Exported: '.now()->format('d/m/Y H:i')]);
            fputcsv($out, []);
            fputcsv($out, $headers);
            $sumExpected = 0;
            $sumTuitionPaid = 0;
            foreach ($students as $index => $student) {
                $row = $this->buildAdmissionControlRow($student, $feeStructures, $index + 1, $semesterNumber);
                $sumExpected += $row['expected_tuition'];
                $sumTuitionPaid += $row['tuition_paid'];
                fputcsv($out, $this->admissionControlSheetCsvValues($row));
            }
            fputcsv($out, []);
            $totalRow = array_fill(0, count($headers), '');
            $totalRow[0] = 'TOTAL';
            $totalRow[10] = $sumExpected;
            $totalRow[11] = $sumTuitionPaid;
            fputcsv($out, $totalRow);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FeeStructure>  $feeStructures
     * @return array<string, mixed>
     */
    private function buildAdmissionControlRow(Student $student, $feeStructures, int $sn, int $semesterNumber): array
    {
        $fs = $this->resolveFeeStructureForStudent($feeStructures, $student);
        $expectedTuition = $fs ? $fs->expectedTuitionForSemester($semesterNumber, $student) : 0.0;
        if ($fs && $semesterNumber === 2) {
            $paidSemesterTwo = $student->payments->contains(fn ($p) => (bool) $p->covers_semester_two_only);
            if ($paidSemesterTwo) {
                $expectedTuition += $fs->expectedTuitionForSemester(1, $student);
            }
        }
        $tuitionPaid = $student->sumPaymentAllocation('tuition');

        $tuitionPaymentRef = $student->paymentRefsForComponent('tuition', $student->tuition_payment_ref);

        $allocation = $student->accommodationAllocations->first();
        $hostelBlock = $allocation && $allocation->room
            ? ($allocation->room->hostel->name ?? $allocation->room->hostel->code ?? $allocation->room->name ?? '')
            : '';

        $yearOfStudy = isset(Student::NTA_LEVELS[$student->nta_level])
            ? (string) (array_search($student->nta_level, array_keys(Student::NTA_LEVELS), true) + 1)
            : ($student->nta_level ? (string) $student->nta_level : '');

        $expectedNhif = $fs ? $fs->expectedNhifForSemester($semesterNumber, $student) : 0.0;
        $expectedNactvetQa = $fs ? $fs->expectedNactvetQaForSemester($semesterNumber) : 0.0;
        $nhifPaid = $student->sumPaymentAllocation('nhif');
        $nactvetPaid = $student->sumPaymentAllocation('nactvet_qa');

        $nhifExempt = $semesterNumber === 1 && $student->has_personal_nhif;
        $nhifStatus = $student->admissionFeeComponentStatusLabel(
            $expectedNhif,
            $nhifPaid,
            $student->nhif_status,
            $nhifExempt,
            'EXEMPT — Personal NHIF',
            $semesterNumber
        );

        $nhifFeeCell = '';
        if ($semesterNumber === 1) {
            if ($student->has_personal_nhif) {
                $nhifFeeCell = '—';
            } elseif ($expectedNhif > 0) {
                $nhifFeeCell = (int) round($expectedNhif);
            } elseif ($fs) {
                $nhifFeeCell = 0;
            }
        }

        $nactvetQaFeeCell = $semesterNumber === 1 ? (int) round($expectedNactvetQa) : '';

        return [
            'sn' => $sn,
            'name' => $student->full_name,
            'gender' => $student->gender ?? '',
            'registration_number' => $student->registrationNumberDisplay(),
            'nta_level' => $student->nta_level ? (Student::NTA_LEVELS[$student->nta_level] ?? 'NTA Level '.$student->nta_level) : '',
            'programme' => $student->programme ? $student->programme->name : '',
            'year_of_study' => $yearOfStudy,
            'reporting_status' => $student->reporting_status ? (Student::REPORTING_STATUSES[$student->reporting_status] ?? $student->reporting_status) : '',
            'reporting_date' => $student->reporting_date?->format('d/m/Y') ?? '',
            'reporting_date_csv' => $student->reporting_date?->format('Y-m-d') ?? '',
            'tuition_fee_status' => $student->admissionTuitionStatusLabel($expectedTuition, $tuitionPaid),
            'expected_tuition' => (int) round($expectedTuition),
            'tuition_paid' => (int) round($tuitionPaid),
            'payment_reference' => $tuitionPaymentRef,
            'tuition_control_number' => $tuitionPaymentRef,
            'when_complete_tuition' => $student->tuition_completion_pledge_date?->format('d/m/Y') ?? '',
            'when_complete_tuition_csv' => $student->tuition_completion_pledge_date?->format('Y-m-d') ?? '',
            'nhif_fee' => $nhifFeeCell,
            'nhif_status' => $nhifStatus,
            'nhif_paid' => $semesterNumber === 1 && ! $nhifExempt ? (int) round($nhifPaid) : '',
            'nactvet_qa_fee' => $nactvetQaFeeCell,
            'nactvet_qa_paid' => $semesterNumber === 1 ? (int) round($nactvetPaid) : '',
            'nactvet_qa_status' => $student->admissionFeeComponentStatusLabel(
                $expectedNactvetQa,
                $nactvetPaid,
                $student->nactvet_qa_status,
                false,
                null,
                $semesterNumber
            ),
            'nactvet_qa_payment_ref' => $student->paymentRefsForComponent('nactvet_qa', $student->nactvet_qa_payment_ref),
            'joining_instructions_submitted' => $student->joining_instructions_submitted ?? '',
            'academic_requirements' => $student->admissionCertificatesDisplay(),
            'certificates_submitted' => $student->submittedCertificatesLabel(),
            'hostel_allocation' => $hostelBlock,
            'class_group' => $student->class_group ?? '',
            'class_property_received' => $student->class_property_received ?? '',
            'chair_number' => $student->chair_number ?? '',
            'table_number' => $student->table_number ?? '',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FeeStructure>  $structures
     */
    private function resolveFeeStructureForStudent($structures, Student $student): ?FeeStructure
    {
        $specific = $structures->firstWhere('programme_id', $student->programme_id);
        if ($specific) {
            return $specific;
        }

        return $structures->firstWhere('programme_id', null);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<mixed>
     */
    private function admissionControlSheetCsvValues(array $row): array
    {
        return [
            $row['sn'],
            $row['name'],
            $row['gender'],
            $row['registration_number'],
            $row['nta_level'],
            $row['programme'],
            $row['year_of_study'],
            $row['reporting_status'],
            $row['reporting_date_csv'],
            $row['tuition_fee_status'],
            $row['expected_tuition'],
            $row['tuition_paid'],
            $row['tuition_control_number'],
            $row['when_complete_tuition_csv'],
            $row['nhif_fee'],
            $row['nhif_paid'],
            $row['nhif_status'],
            $row['nactvet_qa_fee'],
            $row['nactvet_qa_paid'],
            $row['nactvet_qa_status'],
            $row['nactvet_qa_payment_ref'],
            $row['joining_instructions_submitted'],
            $row['academic_requirements'],
            $row['hostel_allocation'],
            $row['class_group'],
            $row['class_property_received'],
            $row['chair_number'],
            $row['table_number'],
        ];
    }

    public function studentsOnLeave()
    {
        $applications = LeaveApplication::with('staffUser')
            ->where('status', 'approved')
            ->where('to_date', '>=', now()->toDateString())
            ->orderBy('to_date')
            ->get();

        return view('reports.students-on-leave', compact('applications'));
    }

    public function academicStanding(Request $request)
    {
        $standing = $request->get('standing');
        $query = Student::with('programme')->where('status', 'active');
        if ($standing) {
            $query->where('academic_standing', $standing);
        }
        $students = $query->orderBy('reg_no')->get();

        return view('reports.academic-standing', compact('students', 'standing'));
    }

    public function graduationClearance(Request $request)
    {
        $cleared = $request->get('cleared', 'all');
        $query = GraduationClearance::with('student.programme');
        if ($cleared === 'yes') {
            $query->where('library_cleared', 'yes')->where('finance_cleared', 'yes')
                ->where('accommodation_cleared', 'yes')->where('academic_cleared', 'yes');
        } elseif ($cleared === 'no') {
            $query->where(function ($q) {
                $q->where('library_cleared', '!=', 'yes')
                    ->orWhere('finance_cleared', '!=', 'yes')
                    ->orWhere('accommodation_cleared', '!=', 'yes')
                    ->orWhere('academic_cleared', '!=', 'yes');
            });
        }
        $clearances = $query->orderBy('student_id')->get();

        return view('reports.graduation-clearance', compact('clearances', 'cleared'));
    }

    public function export(Request $request)
    {
        return view('reports.export');
    }

    public function exportRun(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'students');
        $filename = 'cohas-export-'.$type.'-'.date('Y-m-d').'.csv';
        if ($type === 'students') {
            $students = Student::with('programme')->orderBy('reg_no')->get();

            return response()->streamDownload(function () use ($students) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Reg No', 'NACTVET No', 'First Name', 'Last Name', 'Programme', 'Intake Year', 'Status']);
                foreach ($students as $s) {
                    fputcsv($out, [$s->reg_no, $s->nactvet_reg_no, $s->first_name, $s->last_name, $s->programme->code ?? '', $s->intake_year, $s->status]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        if ($type === 'payments') {
            $payments = Payment::query()->with('student')->whereHas('student')->orderByDesc('paid_at')->get();

            return response()->streamDownload(function () use ($payments) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date', 'Reg No', 'Student', 'Amount', 'Method', 'Reference']);
                foreach ($payments as $p) {
                    fputcsv($out, [$p->paid_at->format('Y-m-d H:i'), $p->student->reg_no ?? '', $p->student->full_name ?? '', $p->amount, $p->payment_method, $p->reference ?? '']);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }
        abort(400, 'Invalid export type.');
    }

    public function nactvetHub()
    {
        $academicYear = \App\Support\AcademicSession::defaultStartYear();

        return view('reports.nactvet-hub', compact('academicYear'));
    }

    public function nactvetExport(Request $request): StreamedResponse
    {
        $academicYear = (int) $request->get('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $students = Student::with('programme')->where('status', 'active')->orderBy('programme_id')->orderBy('reg_no')->get();
        $filename = 'nactvet-students-'.$academicYear.'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['NACTVET_REG_NO', 'REG_NO', 'FULL_NAME', 'PROGRAMME_CODE', 'PROGRAMME_NAME', 'INTAKE_YEAR', 'NTA_LEVEL', 'STATUS', 'EMAIL', 'PHONE']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->nactvet_reg_no ?? '',
                    $s->reg_no ?? '',
                    $s->full_name ?? '',
                    $s->programme->code ?? '',
                    $s->programme->name ?? '',
                    $s->intake_year ?? '',
                    $s->nta_level ?? '',
                    $s->status ?? '',
                    $s->email ?? '',
                    $s->phone ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function nactvetResultsExport(Request $request): StreamedResponse
    {
        $semesterId = $request->integer('semester_id');
        $semester = Semester::findOrFail($semesterId);

        $results = Result::with(['student.programme', 'course'])
            ->where('semester_id', $semester->id)
            ->where('status', 'approved')
            ->orderBy('student_id')
            ->get();

        $filename = 'nactvet-results-'.$semester->id.'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($results, $semester) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SEMESTER', 'NACTVET_REG_NO', 'REG_NO', 'STUDENT_NAME', 'PROGRAMME', 'MODULE_CODE', 'MODULE_NAME', 'CA', 'SE', 'FINAL', 'GRADE']);
            foreach ($results as $r) {
                fputcsv($out, [
                    $semester->label ?? $semester->id,
                    $r->student->nactvet_reg_no ?? '',
                    $r->student->reg_no ?? '',
                    $r->student->full_name ?? '',
                    $r->student->programme->code ?? '',
                    $r->course->code ?? '',
                    $r->course->name ?? '',
                    $r->ca_mark,
                    $r->exam_mark,
                    $r->total_mark,
                    $r->grade,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function alumniExport(Request $request): StreamedResponse
    {
        $year = (int) $request->get('graduation_year', now()->year);
        $students = Student::with('programme')
            ->where('status', 'graduated')
            ->whereNotNull('graduated_at')
            ->whereYear('graduated_at', $year)
            ->orderBy('programme_id')
            ->orderBy('reg_no')
            ->get();

        $filename = 'alumni-graduates-'.$year.'-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['REG_NO', 'NACTVET_REG_NO', 'NAME', 'PROGRAMME', 'GRADUATED_AT', 'EMAIL', 'PHONE']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->reg_no,
                    $s->nactvet_reg_no ?? '',
                    $s->full_name,
                    $s->programme->code ?? '',
                    $s->graduated_at?->format('Y-m-d') ?? '',
                    $s->email ?? '',
                    $s->phone ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function studentsWithArrears()
    {
        $balanceSubQuery = DB::table('ledger_entries')
            ->selectRaw("
                student_id,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END)
                - SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) AS balance
            ")
            ->groupBy('student_id');

        return Student::query()
            ->with('programme')
            ->select('students.*', 'ledger_balances.balance')
            ->joinSub($balanceSubQuery, 'ledger_balances', function ($join) {
                $join->on('students.id', '=', 'ledger_balances.student_id');
            })
            ->where('students.status', 'active')
            ->where('ledger_balances.balance', '>', 0)
            ->orderByDesc('ledger_balances.balance')
            ->get()
            ->each(function (Student $student): void {
                $student->setAttribute('balance', (float) round((float) $student->balance, 0));
            });
    }
}

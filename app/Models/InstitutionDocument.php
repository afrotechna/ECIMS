<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionDocument extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    /** Folder keys used in UI and storage path (institution-documents/{key}/year). */
    public const CATEGORIES = [
        'practicum_guide' => 'Practicum guide',
        'sop' => 'SOP',
        'assessment_plan' => 'Assessment plan',
        'curriculum' => 'Curriculum',
        'minutes' => 'Minutes',
        'accreditation' => 'Accreditation & QA',
        'policy' => 'Policies & regulations',
        'finance' => 'Finance & audit',
        'strategic_plan' => 'Strategic plan',
        'staff_handbook' => 'Staff handbook',
        'general' => 'General',
    ];

    /** Folders shown to students in College documents (academic / student-facing). */
    public const STUDENT_CATEGORIES = [
        'practicum_guide',
        'sop',
        'assessment_plan',
        'curriculum',
        'general',
    ];

    /**
     * @return array<string, string>
     */
    public static function categoriesForStudents(): array
    {
        return array_intersect_key(self::CATEGORIES, array_flip(self::STUDENT_CATEGORIES));
    }

    public static function isStudentCategory(string $category): bool
    {
        return in_array($category, self::STUDENT_CATEGORIES, true);
    }

    /**
     * @return array<string, string>
     */
    public static function folderIcons(): array
    {
        return [
            'practicum_guide' => 'bi-journal-medical',
            'sop' => 'bi-file-earmark-ruled',
            'assessment_plan' => 'bi-clipboard-check',
            'curriculum' => 'bi-book',
            'minutes' => 'bi-file-earmark-text',
            'accreditation' => 'bi-patch-check',
            'policy' => 'bi-shield-check',
            'finance' => 'bi-cash-stack',
            'strategic_plan' => 'bi-bullseye',
            'staff_handbook' => 'bi-people',
            'general' => 'bi-folder2',
        ];
    }

    public function scopePublicOnly($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeInStudentFolders($query)
    {
        return $query->whereIn('category', self::STUDENT_CATEGORIES);
    }

    /** Documents for all programmes, or for one programme only. */
    public function scopeVisibleToProgramme($query, ?int $programmeId)
    {
        return $query->where(function ($q) use ($programmeId) {
            $q->whereNull('programme_id');
            if ($programmeId) {
                $q->orWhere('programme_id', $programmeId);
            }
        });
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function programmeLabel(): string
    {
        if (! $this->programme_id) {
            return 'All programmes';
        }

        $code = $this->programme?->code;

        return $code ?: 'Programme #'.$this->programme_id;
    }

    public function canStudentAccess(?Student $student): bool
    {
        if (! $this->is_public || ! self::isStudentCategory($this->category)) {
            return false;
        }

        if ($this->programme_id === null) {
            return true;
        }

        return $student !== null && (int) $student->programme_id === (int) $this->programme_id;
    }

    public function folderIcon(): string
    {
        return self::folderIcons()[$this->category] ?? 'bi-folder2';
    }

    protected $fillable = [
        'title',
        'category',
        'programme_id',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'is_public',
        'uploaded_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProgrammeNtaLevelDocument extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    public const TYPE_ASSESSMENT_PLAN = 'assessment_plan';

    public const TYPE_PRACTICUM_GUIDE = 'practicum_guide';

    public const TYPE_CURRICULUM = 'curriculum';

    /** @var list<string> */
    public const DOCUMENT_TYPES = [
        self::TYPE_ASSESSMENT_PLAN,
        self::TYPE_PRACTICUM_GUIDE,
        self::TYPE_CURRICULUM,
    ];

    protected $table = 'programme_nta_level_documents';

    protected $fillable = [
        'programme_id',
        'nta_level',
        'document_type',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ProgrammeNtaLevelDocument $document): void {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_ASSESSMENT_PLAN => 'Assessment plan',
            self::TYPE_PRACTICUM_GUIDE => 'Practicum guide',
            self::TYPE_CURRICULUM => 'Curriculum',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

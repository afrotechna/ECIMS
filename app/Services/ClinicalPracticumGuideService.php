<?php

namespace App\Services;

use App\Models\Programme;
use App\Models\ProgrammeNtaLevelDocument;
use App\Models\Student;
use App\Support\CmtPracticumCatalog;
use Illuminate\Support\Facades\Storage;

class ClinicalPracticumGuideService
{
    public const GUIDES_DIRECTORY = 'clinical-guides';

    /** @var array<int, string> Official PDF filenames in storage/app/public/clinical-guides */
    public const GUIDE_FILES_BY_LEVEL = [
        4 => 'CMT 4 TUTORS  PRACTICUM GUIDE FINAL.pdf',
        5 => 'PRACTICUM GUIDE NTA L5_Tutors.pdf',
        6 => 'NTA LEVEL 6 PG FOR TUTORS REVIEWED OCTOBER 2022- (1).pdf',
    ];

    public const DEFAULT_STORAGE_PATH = 'clinical-guides/cmt-4-tutors-practicum-guide.pdf';

    public const COHAS_GUIDE_FILENAME = 'CMT 4 TUTORS  PRACTICUM GUIDE FINAL.pdf';

    /**
     * @return array{title: string, url: ?string, source: string, uploaded: bool, hint: ?string, nta_level: int, page_count: ?int}|null
     */
    public function guideForStudent(Student $student): ?array
    {
        $nta = (int) ($student->nta_level ?? 0);
        if (! CmtPracticumCatalog::isSupportedLevel($nta)) {
            return null;
        }

        $student->loadMissing('programme');
        $programme = $student->programme;
        $catalog = CmtPracticumCatalog::forLevel($nta);
        $title = $catalog->sourceLabel();
        $source = $catalog->sourceLabel();

        if ($programme && $this->isCmtProgramme($programme)) {
            $doc = ProgrammeNtaLevelDocument::query()
                ->where('programme_id', $programme->id)
                ->where('nta_level', $nta)
                ->where('document_type', ProgrammeNtaLevelDocument::TYPE_PRACTICUM_GUIDE)
                ->first();

            if ($doc?->file_path && Storage::disk('public')->exists($doc->file_path)) {
                return [
                    'title' => $doc->original_name ?: $title,
                    'url' => Storage::disk('public')->url($doc->file_path),
                    'source' => $source,
                    'uploaded' => true,
                    'hint' => null,
                    'nta_level' => $nta,
                    'page_count' => null,
                ];
            }
        }

        $localPath = self::resolveLocalGuidePath($nta);
        if ($localPath !== null) {
            return [
                'title' => basename($localPath),
                'url' => self::publicUrlForPath($localPath),
                'source' => $source,
                'uploaded' => true,
                'hint' => null,
                'nta_level' => $nta,
                'page_count' => self::approximatePageCount($nta),
            ];
        }

        return [
            'title' => $title,
            'url' => null,
            'source' => $source,
            'uploaded' => false,
            'hint' => 'Place the PDF in storage/app/public/'.self::GUIDES_DIRECTORY.'/ (e.g. "'.(self::GUIDE_FILES_BY_LEVEL[$nta] ?? 'practicum-guide.pdf').'") or upload via Programmes → CMT → NTA Level '.$nta.' → Practicum guide.',
            'nta_level' => $nta,
            'page_count' => null,
        ];
    }

    public static function resolveLocalGuidePath(?int $ntaLevel = 4): ?string
    {
        $disk = Storage::disk('public');
        $ntaLevel ??= 4;

        $preferred = [];
        if (isset(self::GUIDE_FILES_BY_LEVEL[$ntaLevel])) {
            $preferred[] = self::GUIDES_DIRECTORY.'/'.self::GUIDE_FILES_BY_LEVEL[$ntaLevel];
        }
        if ($ntaLevel === 4) {
            $preferred[] = self::GUIDES_DIRECTORY.'/'.self::COHAS_GUIDE_FILENAME;
            $preferred[] = self::DEFAULT_STORAGE_PATH;
        }

        foreach ($preferred as $path) {
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function approximatePageCount(int $ntaLevel): ?int
    {
        return match ($ntaLevel) {
            4 => 410,
            5 => 441,
            6 => 350,
            default => null,
        };
    }

    public static function publicUrlForPath(string $path): string
    {
        $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $path)));

        return rtrim(config('app.url', ''), '/').'/storage/'.implode('/', $segments);
    }

    public function isCmtProgramme(?Programme $programme): bool
    {
        if (! $programme) {
            return false;
        }

        return strtoupper(trim((string) $programme->code)) === 'CMT'
            || str_contains(strtolower((string) $programme->name), 'clinical medicine');
    }
}

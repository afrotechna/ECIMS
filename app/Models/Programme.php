<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programme extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'name',
        'code',
        'level',
        'duration_years',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Programme $programme): void {
            $programme->ntaLevelDocuments()->each(fn (ProgrammeNtaLevelDocument $doc) => $doc->delete());
        });
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function ntaLevelDocuments(): HasMany
    {
        return $this->hasMany(ProgrammeNtaLevelDocument::class);
    }

    /**
     * Official programmes staff may add — selection only (no free typing).
     *
     * @return array<int, array{code: string, name: string, legacy?: bool}>
     */
    public static function catalogue(): array
    {
        return [
            ['code' => 'CMT', 'name' => 'Clinical Medicine'],
            ['code' => 'MLT', 'name' => 'Medical Laboratory Science'],
            ['code' => 'CLN', 'name' => 'Clinical Nutrition'],
            ['code' => 'DDR', 'name' => 'Diagnostic Radiography'],
        ];
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public static function rowForCode(string $code): ?array
    {
        $code = strtoupper(trim($code));

        return collect(static::catalogue())->first(fn ($row) => strtoupper($row['code']) === $code);
    }

    /**
     * NTA-style qualification level — select only.
     *
     * @return array<string, string>
     */
    public static function levelSelectOptions(): array
    {
        return [
            'Basic Technician Certificate' => 'Basic Technician Certificate',
            'Technician Certificate' => 'Technician Certificate',
            'Ordinary Diploma' => 'Ordinary Diploma',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedLevelValues(): array
    {
        return array_keys(static::levelSelectOptions());
    }

    /**
     * @return list<int>
     */
    public static function allowedDurationYears(): array
    {
        return [1, 2, 3, 4, 5];
    }

    /**
     * @return array<string, string> value (years as string) => label
     */
    public static function durationYearSelectOptions(): array
    {
        $opts = [];
        foreach (static::allowedDurationYears() as $y) {
            $opts[(string) $y] = $y === 1 ? '1 year' : "{$y} years";
        }

        return $opts;
    }

    /**
     * Catalogue rows for edit, including current programme if it is not in the standard list (legacy).
     *
     * @return array<int, array{code: string, name: string, legacy?: bool}>
     */
    public static function catalogueOptionsForEdit(self $programme): array
    {
        $rows = static::catalogue();
        $standardCodes = collect($rows)->pluck('code')->map(fn ($c) => strtoupper((string) $c))->all();
        $currentUpper = strtoupper((string) $programme->code);
        if (! in_array($currentUpper, $standardCodes, true)) {
            $rows[] = [
                'code' => $programme->code,
                'name' => $programme->name,
                'legacy' => true,
            ];
        }
        usort($rows, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));

        return $rows;
    }

    /**
     * Level dropdown including current value when it is not one of the standard options (legacy records).
     *
     * @return array<string, string>
     */
    public static function levelSelectOptionsForEdit(self $programme): array
    {
        $base = static::levelSelectOptions();
        $current = (string) ($programme->level ?? '');
        if ($current !== '' && ! isset($base[$current])) {
            $base[$current] = $current.' (on file)';
        }

        return $base;
    }

    /**
     * @return list<string>
     */
    public static function allowedLevelValuesForEdit(self $programme): array
    {
        return array_keys(static::levelSelectOptionsForEdit($programme));
    }

    /**
     * Duration dropdown including current value when outside the standard list.
     *
     * @return array<string, string>
     */
    public static function durationYearSelectOptionsForEdit(self $programme): array
    {
        $years = static::allowedDurationYears();
        $current = (int) ($programme->duration_years ?? 3);
        if (! in_array($current, $years, true)) {
            $years = array_values(array_unique(array_merge($years, [$current])));
            sort($years);
        }

        $opts = [];
        foreach ($years as $y) {
            $opts[(string) $y] = $y === 1 ? '1 year' : "{$y} years";
        }

        return $opts;
    }

    /**
     * @return list<int>
     */
    public static function allowedDurationYearsForEdit(self $programme): array
    {
        return array_map('intval', array_keys(static::durationYearSelectOptionsForEdit($programme)));
    }
}

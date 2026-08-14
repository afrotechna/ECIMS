<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StudentImportSetting extends Model
{
    private const CACHE_KEY = 'student_import_setting';

    protected $fillable = [
        'restrict_single_programme',
        'updated_by',
    ];

    protected $casts = [
        'restrict_single_programme' => 'boolean',
    ];

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::firstOrCreate(['id' => 1]));
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function updatedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

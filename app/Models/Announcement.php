<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_STUDENTS = 'students';

    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCES = [
        self::AUDIENCE_STUDENTS => 'Students only (student portal)',
        self::AUDIENCE_STAFF => 'Staff only (admin dashboard)',
        self::AUDIENCE_ALL => 'Everyone (staff + all students)',
    ];

    protected $fillable = [
        'title',
        'body',
        'show_until',
        'created_by',
        'audience',
        'target_nta_levels',
        'target_programme_ids',
    ];

    protected $casts = [
        'show_until' => 'date',
        'target_nta_levels' => 'array',
        'target_programme_ids' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('show_until')->orWhere('show_until', '>=', now()->toDateString());
        });
    }

    /** Active students matching NTA level / programme filters (empty targets = no limit). */
    public function scopeForStudent(Builder $query, Student $student): Builder
    {
        return $query->where(function (Builder $q) use ($student) {
            $q->whereNull('target_nta_levels')
                ->orWhereJsonLength('target_nta_levels', 0)
                ->when(
                    $student->nta_level !== null && $student->nta_level !== '',
                    fn (Builder $q2) => $q2->orWhereJsonContains('target_nta_levels', (int) $student->nta_level)
                );
        })->where(function (Builder $q) use ($student) {
            $q->whereNull('target_programme_ids')
                ->orWhereJsonLength('target_programme_ids', 0)
                ->when(
                    $student->programme_id,
                    fn (Builder $q2) => $q2->orWhereJsonContains('target_programme_ids', (int) $student->programme_id)
                );
        });
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        $query->visible();

        if ($user->isStudent()) {
            if (! $user->student) {
                return $query->whereRaw('0 = 1');
            }

            return $query
                ->whereIn('audience', [self::AUDIENCE_STUDENTS, self::AUDIENCE_ALL])
                ->forStudent($user->student);
        }

        return $query->whereIn('audience', [self::AUDIENCE_STAFF, self::AUDIENCE_ALL]);
    }

    /** Human-readable targeting for staff list. */
    public function targetingLabel(): string
    {
        $parts = [self::AUDIENCES[$this->audience] ?? $this->audience];

        $levels = $this->target_nta_levels ?? [];
        if ($levels !== []) {
            $labels = collect($levels)->map(fn ($l) => 'NTA '.(int) $l)->implode(', ');
            $parts[] = $labels;
        } else {
            $parts[] = 'All NTA levels';
        }

        $progs = $this->target_programme_ids ?? [];
        if ($progs !== []) {
            $names = Programme::whereIn('id', $progs)->pluck('code')->implode(', ');
            $parts[] = 'Programmes: '.$names;
        }

        return implode(' · ', $parts);
    }
}

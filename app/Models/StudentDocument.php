<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    protected $fillable = ['student_id', 'type', 'name', 'path', 'mime_type', 'size'];

    public const TYPES = ['id_copy' => 'ID copy', 'photo' => 'Photo', 'joining_instructions' => 'Joining instructions (signed)', 'birth_certificate' => 'Birth certificate', 'other' => 'Other'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}

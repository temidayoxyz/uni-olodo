<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_session_id', 'number', 'name', 'starts_on', 'ends_on', 'registration_opens_at', 'registration_closes_at', 'is_current'])]
class Semester extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'is_current' => 'boolean',
            'number' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function registrationIsOpen(): bool
    {
        $now = now();

        return $this->registration_opens_at !== null
            && $this->registration_closes_at !== null
            && $now->between($this->registration_opens_at, $this->registration_closes_at);
    }
}

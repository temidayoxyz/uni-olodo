<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_offering_id', 'title', 'instructions', 'points',
    'available_from', 'due_at', 'late_until', 'published_at',
])]
class Assignment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'available_from' => 'datetime',
            'due_at' => 'datetime',
            'late_until' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function submissionFor(int $studentId): ?AssignmentSubmission
    {
        return $this->submissions()->firstWhere('student_id', $studentId);
    }

    public function isPastDue(): bool
    {
        return now()->isAfter($this->due_at);
    }

    public function acceptsSubmissions(): bool
    {
        $now = now();

        if ($this->available_from !== null && $now->isBefore($this->available_from)) {
            return false;
        }

        if (! $this->isPastDue()) {
            return true;
        }

        return $this->late_until !== null && $now->isBefore($this->late_until);
    }
}

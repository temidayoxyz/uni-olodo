<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_offering_id', 'title', 'instructions', 'duration_minutes',
    'available_from', 'available_until', 'max_attempts', 'shuffle_questions', 'reveal_answers',
    'published_at',
])]
class Quiz extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'reveal_answers' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attemptsBy(int $studentId): HasMany
    {
        return $this->attempts()->where('student_id', $studentId);
    }

    /** Total points available across all questions. */
    public function totalPoints(): float
    {
        return (float) $this->questions()->sum('points');
    }

    public function isAvailable(): bool
    {
        $now = now();

        if ($this->available_from !== null && $now->isBefore($this->available_from)) {
            return false;
        }

        return $this->available_until === null || $now->isBefore($this->available_until);
    }
}

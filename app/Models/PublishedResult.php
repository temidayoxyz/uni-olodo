<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable official result snapshot. Students see results ONLY from this table.
 */
#[Fillable([
    'result_submission_id', 'course_offering_id', 'semester_id', 'student_id',
    'ca_score', 'exam_score', 'total', 'grade_letter', 'grade_point', 'is_passed', 'published_at',
])]
class PublishedResult extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ca_score' => 'float',
            'exam_score' => 'float',
            'total' => 'float',
            'grade_point' => 'float',
            'is_passed' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Official records are immutable once written; updates and deletes are blocked at model level.
        static::updating(function () {
            throw new \RuntimeException('Published results are immutable.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Published results cannot be deleted.');
        });
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ResultSubmission::class, 'result_submission_id');
    }
}

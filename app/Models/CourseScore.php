<?php

namespace App\Models;

use App\Support\GradeScale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provisional component scores (CA out of 40 + exam out of 60).
 * These are internal working figures — official results come only from published_results.
 */
#[Fillable([
    'course_offering_id', 'student_id', 'ca_score', 'exam_score', 'note', 'last_edited_by',
])]
class CourseScore extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ca_score' => 'float',
            'exam_score' => 'float',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function total(): ?float
    {
        if ($this->ca_score === null && $this->exam_score === null) {
            return null;
        }

        return (float) ($this->ca_score ?? 0) + (float) ($this->exam_score ?? 0);
    }

    public function gradeLetter(): ?string
    {
        return GradeScale::letterFor($this->total());
    }
}

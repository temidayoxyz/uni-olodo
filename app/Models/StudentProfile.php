<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'matric_number', 'programme_id', 'level', 'adviser_id', 'status', 'admitted_session_id'])]
class StudentProfile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'status' => StudentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function admittedSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'admitted_session_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'student_id', 'user_id');
    }

    /**
     * Total credit units registered (approved, not dropped) in the given semester.
     */
    public function registeredCreditsFor(Semester $semester): int
    {
        return (int) RegistrationItem::query()
            ->where('registration_items.status', 'registered')
            ->whereHas('registration', fn ($q) => $q
                ->where('student_id', $this->user_id)
                ->where('semester_id', $semester->id)
                ->whereIn('status', ['draft', 'submitted', 'approved']))
            ->join('course_offerings', 'course_offerings.id', '=', 'registration_items.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->sum('courses.credit_units');
    }
}

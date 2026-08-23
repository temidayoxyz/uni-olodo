<?php

namespace App\Models;

use App\Enums\OfferingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'semester_id', 'lecturer_id', 'capacity', 'status'])]
class CourseOffering extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OfferingStatus::class,
            'capacity' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(OfferingSchedule::class)->orderBy('weekday')->orderBy('starts_at');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Students actively enrolled in this offering (approved registrations, not dropped).
     */
    public function enrolledStudents()
    {
        return User::query()
            ->whereHas('registrations.items', fn ($q) => $q
                ->where('course_offering_id', $this->id)
                ->where('status', 'registered')
                ->whereHas('registration', fn ($r) => $r->where('status', 'approved')));
    }

    public function enrolmentCount(): int
    {
        return RegistrationItem::query()
            ->where('course_offering_id', $this->id)
            ->where('status', 'registered')
            ->whereHas('registration', fn ($r) => $r->where('status', 'approved'))
            ->count();
    }

    public function hasSeatsAvailable(): bool
    {
        return $this->capacity === null || $this->enrolmentCount() < $this->capacity;
    }
}

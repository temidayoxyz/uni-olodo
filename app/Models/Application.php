<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'number', 'intake_session_id',
    'first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'phone', 'address',
    'nationality', 'state_of_origin',
    'qualification', 'examination_year', 'previous_school', 'personal_statement',
    'status', 'submitted_at', 'decision_at', 'decided_by', 'decision_note', 'offer_responded_at',
])]
class Application extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'decision_at' => 'datetime',
            'offer_responded_at' => 'datetime',
            'examination_year' => 'integer',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function intakeSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'intake_session_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ApplicationChoice::class)->orderBy('rank');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    // --- State machine helpers (transitions are enforced in ApplicationService) ---

    public function statusIs(ApplicationStatus ...$statuses): bool
    {
        return in_array($this->status, $statuses, true);
    }

    public function hasAcceptedOffer(): bool
    {
        return in_array($this->status, [ApplicationStatus::Enrolled], true)
            && $this->offer_responded_at !== null;
    }
}

<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'body', 'priority', 'pinned', 'author_id', 'published_at', 'expires_at'])]
class Announcement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => 'string',
            'pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(AnnouncementAudience::class);
    }

    public function scopePublished($query)
    {
        $now = now();

        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now));
    }

    /**
     * Announcements this user is actually in the audience for. The database is
     * the boundary — a UI filter must never be what hides a staff announcement.
     */
    public function scopeVisibleTo($query, User $user)
    {
        $roleKey = $user->role === UserRole::Student ? 'student' : ($user->isStaff() ? 'staff' : $user->role->value);
        $profile = $user->relationLoaded('studentProfile') ? $user->studentProfile : $user->studentProfile()->first();
        $profile?->loadMissing('programme.department');
        $enrolledOfferingIds = RegistrationItem::query()
            ->where('registration_items.status', 'registered')
            ->whereHas('registration', fn ($r) => $r->where('student_id', $user->id)->where('status', 'approved'))
            ->pluck('course_offering_id');

        return $query->published()
            ->whereHas('audiences', function ($audiences) use ($roleKey, $profile, $enrolledOfferingIds) {
                $audiences->where('scope', 'university')
                    ->orWhere(fn ($q) => $q->where('scope', 'role')->where('scope_id', $roleKey));

                if ($profile !== null) {
                    $audiences
                        ->orWhere(fn ($q) => $q->where('scope', 'department')->where('scope_id', (string) $profile->programme->department_id))
                        ->orWhere(fn ($q) => $q->where('scope', 'faculty')->where('scope_id', (string) $profile->programme->department->faculty_id))
                        ->orWhere(fn ($q) => $q->where('scope', 'programme')->where('scope_id', (string) $profile->programme_id));
                }

                if ($enrolledOfferingIds->isNotEmpty()) {
                    $audiences->orWhere(fn ($q) => $q->where('scope', 'course_offering')->whereIn('scope_id', $enrolledOfferingIds));
                }
            })
            ->with('author')
            ->orderByDesc('pinned')
            ->orderByDesc('published_at');
    }
}

<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case MoreInfoRequired = 'more_info_required';
    case Accepted = 'accepted';
    case ConditionallyAccepted = 'conditionally_accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Enrolled = 'enrolled'; // offer accepted → became a student

    /**
     * Legal transitions. Anything not listed here is refused server-side.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted, self::MoreInfoRequired => [
                self::UnderReview, self::Withdrawn,
            ],
            self::UnderReview => [
                self::MoreInfoRequired, self::Accepted, self::ConditionallyAccepted,
                self::Waitlisted, self::Rejected,
            ],
            self::Accepted, self::ConditionallyAccepted => [self::Enrolled],
            self::Waitlisted => [self::Accepted, self::Rejected],
            self::Rejected, self::Withdrawn, self::Enrolled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isDecided(): bool
    {
        return match ($this) {
            self::Accepted, self::ConditionallyAccepted, self::Rejected,
            self::Waitlisted, self::Withdrawn, self::Enrolled => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::MoreInfoRequired => 'Additional information required',
            self::Accepted => 'Accepted',
            self::ConditionallyAccepted => 'Conditionally accepted',
            self::Waitlisted => 'Waitlisted',
            self::Rejected => 'Not admitted',
            self::Withdrawn => 'Withdrawn',
            self::Enrolled => 'Enrolled',
        };
    }
}

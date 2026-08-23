<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use PHPUnit\Framework\TestCase;

class ApplicationStatusTest extends TestCase
{
    public function test_draft_can_only_be_submitted(): void
    {
        $this->assertSame([ApplicationStatus::Submitted], ApplicationStatus::Draft->allowedTransitions());
    }

    public function test_officers_cannot_skip_review(): void
    {
        $fromSubmission = [
            ApplicationStatus::UnderReview,
            ApplicationStatus::Withdrawn,
        ];

        $this->assertEqualsCanonicalizing($fromSubmission, ApplicationStatus::Submitted->allowedTransitions());
        $this->assertFalse(ApplicationStatus::Submitted->canTransitionTo(ApplicationStatus::Accepted));
    }

    public function test_review_leads_to_any_decision(): void
    {
        $decisions = [
            ApplicationStatus::MoreInfoRequired, ApplicationStatus::Accepted,
            ApplicationStatus::ConditionallyAccepted, ApplicationStatus::Waitlisted,
            ApplicationStatus::Rejected,
        ];

        foreach ($decisions as $decision) {
            $this->assertTrue(ApplicationStatus::UnderReview->canTransitionTo($decision), "under_review → {$decision->value} should be legal");
        }
    }

    public function test_decided_applications_are_terminal_or_offer_bound(): void
    {
        $this->assertTrue(ApplicationStatus::Rejected->allowedTransitions() === []);
        $this->assertTrue(ApplicationStatus::Withdrawn->allowedTransitions() === []);
        $this->assertFalse(ApplicationStatus::Rejected->canTransitionTo(ApplicationStatus::Accepted));
        $this->assertTrue(ApplicationStatus::Accepted->canTransitionTo(ApplicationStatus::Enrolled));
    }

    public function test_more_info_returns_to_the_queue_not_straight_to_a_decision(): void
    {
        $this->assertTrue(ApplicationStatus::MoreInfoRequired->canTransitionTo(ApplicationStatus::UnderReview));
        $this->assertFalse(ApplicationStatus::MoreInfoRequired->canTransitionTo(ApplicationStatus::Accepted));
    }
}

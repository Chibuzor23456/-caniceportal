<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case NotStarted = 'not_started';
    case PendingReview = 'pending_review';
    case InDiscussion = 'in_discussion';
    case Approved = 'approved';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::PendingReview => 'Pending Review',
            self::InDiscussion => 'In Discussion',
            self::Approved => 'Approved',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
        };
    }

    public function pillColor(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::PendingReview => 'orange',
            self::InDiscussion => 'blue',
            self::Approved => 'green',
            self::Paused => 'orange',
            self::Cancelled => 'red',
        };
    }

    /**
     * Whether this phase's thread still accepts new comments/approval
     * (Section 13: once Approved, the thread locks to read-only).
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Approved, self::Cancelled], true);
    }
}

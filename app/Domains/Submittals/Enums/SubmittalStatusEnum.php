<?php

namespace App\Domains\Submittals\Enums;

enum SubmittalStatusEnum: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case ArchitectReview = 'architect_review';
    case OwnerReview = 'owner_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revise = 'revise';
    case Distributed = 'distributed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::ArchitectReview => 'Architect/Engineer Review',
            self::OwnerReview => 'Owner/Stakeholder Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Revise => 'Revise & Resubmit',
            self::Distributed => 'Distributed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::UnderReview, self::ArchitectReview, self::OwnerReview => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Revise => 'amber',
            self::Distributed => 'teal',
            self::Cancelled => 'zinc',
        };
    }
}

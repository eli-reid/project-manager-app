<?php

namespace App\Domains\Submittals\Permissions;

class SubmittalPermissions
{
    public const VIEW_ANY = [
        'resource' => 'submittals',
        'action' => 'view-any',
        'description' => 'View all submittals',
    ];

    public const VIEW = [
        'resource' => 'submittals',
        'action' => 'view',
        'description' => 'View submittals',
    ];

    public const CREATE = [
        'resource' => 'submittals',
        'action' => 'create',
        'description' => 'Create submittals',
    ];

    public const UPDATE = [
        'resource' => 'submittals',
        'action' => 'update',
        'description' => 'Update submittals',
    ];

    public const SUBMIT = [
        'resource' => 'submittals',
        'action' => 'submit',
        'description' => 'Submit submittals for review',
    ];

    public const REVIEW = [
        'resource' => 'submittals',
        'action' => 'review',
        'description' => 'Review and comment on submittals',
    ];

    public const APPROVE = [
        'resource' => 'submittals',
        'action' => 'approve',
        'description' => 'Approve submittals',
    ];

    public const REJECT = [
        'resource' => 'submittals',
        'action' => 'reject',
        'description' => 'Reject submittals',
    ];

    public const DISTRIBUTE = [
        'resource' => 'submittals',
        'action' => 'distribute',
        'description' => 'Distribute approved submittals',
    ];

    public const CANCEL = [
        'resource' => 'submittals',
        'action' => 'cancel',
        'description' => 'Cancel submittals',
    ];

    public const REVISE = [
        'resource' => 'submittals',
        'action' => 'revise',
        'description' => 'Mark submittals for revision and resubmission',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::SUBMIT,
            self::REVIEW,
            self::APPROVE,
            self::REJECT,
            self::DISTRIBUTE,
            self::CANCEL,
            self::REVISE,
        ];
    }
}

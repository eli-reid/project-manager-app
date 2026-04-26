<?php

namespace App\Domains\Submittals\Permissions;

class SubmittalPermissions
{
    public const CREATE = 'submittals.create';
    public const VIEW = 'submittals.view';
    public const EDIT = 'submittals.edit';
    public const SUBMIT = 'submittals.submit';
    public const APPROVE = 'submittals.approve';
    public const REJECT = 'submittals.reject';
    public const COMMENT = 'submittals.comment';
    public const DISTRIBUTE = 'submittals.distribute';
    public const MANAGE = 'submittals.manage';

    public static function all(): array
    {
        return [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::SUBMIT,
            self::APPROVE,
            self::REJECT,
            self::COMMENT,
            self::DISTRIBUTE,
            self::MANAGE,
        ];
    }
}

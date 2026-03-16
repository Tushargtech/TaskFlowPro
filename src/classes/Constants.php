<?php

declare(strict_types=1);

class Constants
{
    public const USER_STATUS_ACTIVE = 'Active';
    public const USER_STATUS_INACTIVE = 'Inactive';
    public const USER_STATUSES = [
        self::USER_STATUS_ACTIVE,
        self::USER_STATUS_INACTIVE
    ];

    public const PROJECT_STATUS_ACTIVE = 'Active';
    public const PROJECT_STATUS_INACTIVE = 'Inactive';
    public const PROJECT_STATUSES = [
        self::PROJECT_STATUS_ACTIVE,
        self::PROJECT_STATUS_INACTIVE
    ];

    public const TASK_STATUS_DUE = 'Due';
    public const TASK_STATUS_COMPLETED = 'Completed';
    public const TASK_STATUS_INACTIVE = 'Inactive';
    public const TASK_STATUSES = [
        self::TASK_STATUS_DUE,
        self::TASK_STATUS_COMPLETED,
        self::TASK_STATUS_INACTIVE
    ];

    public const ROLE_ADMIN = 1;
    public const ROLE_USER = 2;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';

    public const MSG_PROJECT_CREATED = 'project_created';
    public const MSG_PROJECT_UPDATED = 'project_updated';
    public const MSG_TASK_COMPLETED = 'task_completed';
    public const MSG_TASK_UPDATED = 'task_updated';
    public const MSG_INVALID_INPUT = 'invalid_input';
    public const MSG_UNAUTHORIZED = 'unauthorized';

    public static function getRoleName(int $roleId): string
    {
        $roleNames = [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_USER => 'User'
        ];
        return $roleNames[$roleId] ?? 'Unknown';
    }
}


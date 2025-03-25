<?php

namespace App\Enums;

enum UserRoles: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('enums.user_roles.admin'),
            self::EMPLOYEE => __('enums.user_roles.employee'),
            self::USER => __('enums.user_roles.user'),
        };
    }
}
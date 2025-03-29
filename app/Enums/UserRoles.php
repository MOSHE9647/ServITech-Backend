<?php

namespace App\Enums;

enum UserRoles: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case USER = 'user';

    /**
     * Get the label for the enum value.
     * This method returns the translated label for the enum value.
     * It uses the match expression to determine the correct label based on the enum case.
     * The labels are defined in the language files, allowing for easy localization.
     * @return array|string|null
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('enums.user_roles.admin'),
            self::EMPLOYEE => __('enums.user_roles.employee'),
            self::USER => __('enums.user_roles.user'),
        };
    }
}
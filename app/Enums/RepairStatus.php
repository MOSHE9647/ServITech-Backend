<?php

namespace App\Enums;

enum RepairStatus: string
{
    case PENDING = 'pending';      // Pending review
    case IN_PROGRESS = 'in_progress'; // Under repair
    case WAITING_PARTS = 'waiting_parts'; // Waiting for parts
    case COMPLETED = 'completed';  // Repaired
    case DELIVERED = 'delivered';  // Delivered to the client
    case CANCELED = 'canceled';    // Canceled

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
            self::PENDING => __('enums.repair_status.pending'),
            self::IN_PROGRESS => __('enums.repair_status.in_progress'),
            self::WAITING_PARTS => __('enums.repair_status.waiting_parts'),
            self::COMPLETED => __('enums.repair_status.completed'),
            self::DELIVERED => __('enums.repair_status.delivered'),
            self::CANCELED => __('enums.repair_status.canceled'),
        };
    }
}
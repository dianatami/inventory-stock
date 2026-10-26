<?php

namespace App\Services;

use App\Models\SystemNotification;

class SystemNotificationService
{
    public function record(
        string $eventKey,
        string $category,
        string $title,
        string $message,
        array $audience,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNumber = null
    ): SystemNotification {
        return SystemNotification::firstOrCreate(
            ['event_key' => $eventKey],
            [
                'category' => $category,
                'title' => $title,
                'message' => $message,
                'audience' => array_values(array_unique($audience)),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
            ]
        );
    }
}

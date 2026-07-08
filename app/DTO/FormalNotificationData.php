<?php

namespace App\DTO;

use Carbon\Carbon;

readonly class FormalNotificationData
{
    public function __construct(
        public string $subject,
        public string $recipientName,
        public string $headline,
        public string $message,
        public Carbon $eventAt,
        public ?string $eventTimezone = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $footerNote = null,
    ) {}

    public function formattedEventAt(): string
    {
        $timezone = $this->eventTimezone ?? config('conductor-ledger.timezone', 'America/El_Salvador');

        return $this->eventAt->copy()->timezone($timezone)->format('d/m/Y H:i:s T');
    }
}

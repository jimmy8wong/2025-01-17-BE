<?php

namespace App\Message;

final class AttendeeConfirmationEmailMessage
{
    public function __construct(
        private int $attendeeId,
    ) {
    }

    public function getAttendeeId(): int
    {
        return $this->attendeeId;
    }
}

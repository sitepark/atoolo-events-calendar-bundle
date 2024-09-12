<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Types;

class EventDate
{
    public function __construct(
        public readonly \DateTime $start,
        public readonly ?\DateTime $end = null,
        public readonly ?string $rrule = null,
        public readonly bool $isFullDay = false,
        public readonly bool $hasStartTime = true,
        public readonly bool $hasEndTime = true,
        public readonly ?string $status = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\RceEvent;

class RceEventCategory
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }
}

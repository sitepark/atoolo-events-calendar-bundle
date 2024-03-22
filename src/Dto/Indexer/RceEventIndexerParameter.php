<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\Indexer;

class RceEventIndexerParameter
{
    public function __construct(
        public readonly string $rceEventListZip,
        public readonly int $cleanupThreshold = 0,
    ) {
    }
}

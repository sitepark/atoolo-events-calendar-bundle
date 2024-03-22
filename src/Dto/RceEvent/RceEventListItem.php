<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\RceEvent;

/**
 * @codeCoverageIgnore
 */
class RceEventListItem
{
    /**
     * @param array<RceEventDate> $dates
     * @param array<RceEventUpload> $uploads
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $active,
        public readonly array $dates,
        public readonly string $description,
        public readonly bool $online,
        public readonly bool $onsite,
        public readonly string $ticketLink,
        public readonly ?RceEventCategory $theme,
        public readonly ?RceEventCategory $subTheme,
        public readonly bool $highlight,
        public readonly ?RceEventSource $source,
        public readonly RceEventAddresses $addresses,
        public readonly string $keywords,
        public readonly array $uploads
    ) {
    }
}

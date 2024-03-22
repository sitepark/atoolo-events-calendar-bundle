<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\RceEvent;

/**
 * @codeCoverageIgnore
 */
class RceEventListHttpClient
{
    public function get(string $url): string
    {
        return file_get_contents($url);
    }
}

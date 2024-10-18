<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver;

use Atoolo\EventsCalendar\Scheduling;
use Atoolo\GraphQL\Search\Resolver\Resolver;

class SchedulingResolver implements Resolver
{
    public function getRRule(
        Scheduling $scheduling,
    ): ?string {
        if (!$scheduling->hasRRule()) {
            return null;
        }
        // the lib also prints 'DTSTART=...' and 'RRULE:' which is not wanted here
        return preg_replace(
            '/^.*RRULE:/s',
            '',
            $scheduling->getRRule()->rfcString(false),
        );
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Service\GraphQL\SchedulingToEventDateConverter;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventDate;
use Atoolo\Resource\Resource;

class ResourceEventDateResolver
{
    /**
     * @return EventDate[]
     */
    public function getEventDates(
        Resource $resource,
    ): ?array {
        $schedulingRaws = $resource->data->getArray('metadata.schedulingRaw');
        $eventDates = [];
        $converter = new SchedulingToEventDateConverter();
        foreach ($schedulingRaws as $schedulingRaw) {
            // @phpstan-ignore argument.type
            $eventDate = $converter->rawSchedulingToEventDate($schedulingRaw);
            if ($eventDate !== null) {
                $eventDates[] = $eventDate;
            }
        }
        return $eventDates;
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Service\GraphQL\Factory\EventDateFactory;
use Atoolo\EventsCalendar\Service\GraphQL\SchedulingToEventDateConverter;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventDate;
use Atoolo\Resource\Resource;

class ResourceEventDateResolver
{
    public function __construct(
        private readonly EventDateFactory $eventDateFactory,
    ) {}

    /**
     * @return EventDate[]
     */
    public function getEventDates(
        Resource $resource,
    ): ?array {
        $schedulingRaws = $resource->data->getArray('metadata.schedulingRaw');
        $eventDates = [];
        foreach ($schedulingRaws as $schedulingRaw) {
            // @phpstan-ignore argument.type
            $eventDate = $this->eventDateFactory->createFromRawSchedulung($schedulingRaw);
            if ($eventDate !== null) {
                $eventDates[] = $eventDate;
            }
        }
        return $eventDates;
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Factory\SchedulingFactory;
use Atoolo\EventsCalendar\Service\Scheduling\SchedulingManager;
use Atoolo\Resource\Resource;

class ResourceSchedulingResolver
{
    public function __construct(
        private readonly SchedulingFactory $schedulingFactory,
        private readonly SchedulingManager $schedulingManager = new SchedulingManager(),
    ) {}

    /**
     * For a hit of a single date only this date, without repetition.
     *
     * @return Scheduling[]
     */
    public function getSchedulings(
        Resource $resource,
    ): array {
        $schedulings = $this->schedulingFactory->create($resource);
        $occurrence = DateOccurrence::fromResource($resource);
        if ($occurrence === null) {
            return $schedulings;
        }
        $scheduling = $this->schedulingManager->findOccurrenceOfSchedulings($schedulings, $occurrence);
        return $scheduling !== null ? [$scheduling] : $schedulings;
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Scheduling;
use Atoolo\EventsCalendar\SchedulingFactory;
use Atoolo\Resource\Resource;

class ResourceSchedulingResolver
{
    public function __construct(
        private readonly SchedulingFactory $schedulingFactory,
    ) {}

    /**
     * @return Scheduling[]
     */
    public function getSchedulings(
        Resource $resource,
    ): array {
        return $this->schedulingFactory->create($resource);
    }
}

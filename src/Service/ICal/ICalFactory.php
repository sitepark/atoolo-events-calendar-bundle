<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\ICal;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Factory\SchedulingFactory;
use Atoolo\EventsCalendar\Service\ICal\Eluceo\CustomEvent;
use Atoolo\EventsCalendar\Service\ICal\Eluceo\CustomEventFactory;
use Atoolo\EventsCalendar\Service\Scheduling\SchedulingManager;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannelFactory;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\MultiDay;
use Eluceo\iCal\Domain\ValueObject\Occurrence;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;

class ICalFactory
{
    public const CALENDAR_PRODID = '-//atoolo/events-calendar-bundle//1.0/EN';

    public function __construct(
        protected readonly SchedulingFactory $schedulingFactory,
        protected readonly ResourceChannelFactory $resourceChannelFactory,
    ) {}

    public function createCalendarAsString(
        Resource ...$resources,
    ): string {
        return (string) (new CalendarFactory(new CustomEventFactory()))
            ->createCalendar($this->createCalendar(...$resources));
    }

    public function createCalendar(
        Resource ...$resources,
    ): Calendar {
        $resourceChannel = $this->resourceChannelFactory->create();
        $events = [];
        foreach ($resources as $resource) {
            $schedulings = $this->schedulingFactory->create($resource);
            if (!empty($schedulings)) {
                $summary = $resource->data->getString(
                    'metadata.headline',
                    $resource->data->getString('metadata.headline'),
                );
                $description = $resource->data->getString('metadata.description');
                $resourceChannel = $this->resourceChannelFactory->create();
                $isExternal = str_starts_with($resource->location, 'http://')
                    || str_starts_with($resource->location, 'https://');
                $url = $isExternal
                    ? $resource->location
                    : 'https://' . $resourceChannel->serverName . $resource->location;
                $firstEventUid = null;
                foreach ($schedulings as $index => $scheduling) {
                    $uuid = $resource->id . '-' . $index . '@' . $resourceChannel->serverName;
                    $event = new CustomEvent(new UniqueIdentifier($uuid));
                    if ($firstEventUid === null) {
                        $firstEventUid = $event->getUniqueIdentifier();
                    } else {
                        $event->setRelatedTo($firstEventUid);
                    }
                    if (!empty($summary)) {
                        $event->setSummary($summary);
                    }
                    if (!empty($description)) {
                        $event->setDescription($description);
                    }
                    $event->setUrl(new Uri($url));
                    $event->setOccurrence(
                        $this->createOccurenceFromScheduling($scheduling),
                    );
                    $event->setRRule($scheduling->rRule);
                    $events[] = $event;
                }
            }
        }
        $calendar = new Calendar($events);
        $calendar->setProductIdentifier(self::CALENDAR_PRODID);
        return $calendar;
    }

    public function createOccurenceFromScheduling(
        Scheduling $scheduling,
    ): Occurrence {
        if ($scheduling->isFullDay) {
            return (new SchedulingManager())->isMultiDay($scheduling)
                ? new MultiDay(
                    new Date($scheduling->start),
                    new Date($scheduling->end),
                )
                : new SingleDay(new Date($scheduling->start));
        }
        $start = $scheduling->start;
        if (!$scheduling->hasStartTime) {
            $start = (clone $scheduling->start)->setTime(0, 0);
        }
        $end = $scheduling->end ?? $scheduling->start;
        if (!$scheduling->hasEndTime) {
            $end = (clone $end)->setTime(23, 59, 59, 999999);
        }
        return new TimeSpan(
            new DateTime($start, true),
            new DateTime($end, true),
        );
    }
}

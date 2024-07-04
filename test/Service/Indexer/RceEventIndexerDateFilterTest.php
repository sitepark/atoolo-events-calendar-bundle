<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\Indexer;

use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\EventsCalendar\Service\Indexer\RceEventIndexerDateFilter;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RceEventIndexerDateFilter::class)]
class RceEventIndexerDateFilterTest extends TestCase
{
    public function testWithDefaultDate(): void
    {
        $filter = new RceEventIndexerDateFilter();

        $now = new DateTime();
        $event = $this->createStub(RceEventListItem::class);
        $eventDate  = new RceEventDate(
            hashId: '',
            startDate: $now,
            endDate: $now,
            blacklisted: false,
            soldOut: false,
            cancelled: false,
        );

        $this->assertTrue(
            $filter->accept($event, $eventDate),
            'Event should be accepted',
        );
    }

    public function testWithGivenDate(): void
    {
        $filter = new RceEventIndexerDateFilter(
            new DateTime('2023-07-04'),
        );

        $date = new DateTime('2023-07-05');
        $event = $this->createStub(RceEventListItem::class);
        $eventDate  = new RceEventDate(
            hashId: '',
            startDate: $date,
            endDate: $date,
            blacklisted: false,
            soldOut: false,
            cancelled: false,
        );

        $this->assertTrue(
            $filter->accept($event, $eventDate),
            'Event should be accepted',
        );
    }
}

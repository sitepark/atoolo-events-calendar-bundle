<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Factory\SchedulingFactory;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceSchedulingResolver;
use Atoolo\Resource\Resource;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceSchedulingResolver::class)]
class ResourceSchedulingResolverTest extends TestCase
{
    private ResourceSchedulingResolver $resolver;

    private SchedulingFactory&MockObject $schedulingFactory;

    public function setUp(): void
    {
        $this->schedulingFactory = $this->createMock(
            SchedulingFactory::class,
        );
        $this->resolver = new ResourceSchedulingResolver(
            $this->schedulingFactory,
        );
    }

    public function testGetSchedulings(): void
    {
        $resource = Resource::create([]);
        $schedulingsExpected = [
            new Scheduling(
                new DateTime(),
                null,
                false,
                true,
                false,
                null,
            ),
        ];
        $this->schedulingFactory
            ->method('create')
            ->willReturn($schedulingsExpected);
        $schedulings = $this->resolver->getSchedulings($resource);
        $this->assertNotEmpty($schedulings);
        $this->assertEquals(
            $schedulingsExpected,
            $schedulings,
        );
    }

    public function testGetSchedulingsOfDateOccurrence(): void
    {
        $series = $this->createWeeklySeries();
        $this->schedulingFactory
            ->method('create')
            ->willReturn([$series]);
        $second = (clone $series->start)->modify('+1 week');
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => '/event?date=' . $second->getTimestamp(),
        ]);

        $schedulings = $this->resolver->getSchedulings($resource);

        $this->assertCount(1, $schedulings, 'only the date of the hit');
        $this->assertEquals($second->getTimestamp(), $schedulings[0]->start->getTimestamp(), 'start of the date');
        $this->assertNull($schedulings[0]->rRule, 'without repetition');
    }

    public function testGetSchedulingsOfUnknownDateOccurrence(): void
    {
        $series = $this->createWeeklySeries();
        $this->schedulingFactory
            ->method('create')
            ->willReturn([$series]);
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => '/event?date=' . ($series->start->getTimestamp() + 3600),
        ]);

        $this->assertEquals(
            [$series],
            $this->resolver->getSchedulings($resource),
            'all schedulings if the date is not one of the event',
        );
    }

    private function createWeeklySeries(): Scheduling
    {
        return new Scheduling(
            new DateTime('2060-08-04 20:00:00', new \DateTimeZone('Europe/Berlin')),
            null,
            false,
            true,
            false,
            'FREQ=WEEKLY;INTERVAL=1;COUNT=4',
        );
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Service\GraphQL\Factory\EventDateFactory;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceEventDateResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventDate;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceEventDateResolver::class)]
class ResourceEventDateResolverTest extends TestCase
{
    private ResourceEventDateResolver $resolver;

    private EventDateFactory&MockObject $eventDateFactory;

    public function setUp(): void
    {
        $this->eventDateFactory = $this->createMock(
            EventDateFactory::class,
        );
        $this->resolver = new ResourceEventDateResolver(
            $this->eventDateFactory,
        );
    }

    public function testGetEventDates(): void
    {
        $resource = $this->createResource([
            'metadata' => [
                'schedulingRaw' => [
                    [
                        'type' => 'single',
                        'isFullDay' => false,
                        'beginDate' => 1725487200,
                        'beginTime' => '11:00',
                        'endTime' => '12:00',
                    ],
                ],
            ],
        ]);
        $eventDateExpected = new EventDate(new DateTime());
        $this->eventDateFactory
            ->method('createFromRawSchedulung')
            ->willReturn($eventDateExpected);
        $eventDates = $this->resolver->getEventDates($resource);
        $this->assertNotEmpty($eventDates);
        foreach ($eventDates as $eventDate) {
            $this->assertEquals(
                $eventDateExpected,
                $eventDate,
            );
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createResource(array $data): Resource
    {
        return new Resource(
            $data['url'] ?? '',
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['objectType'] ?? '',
            ResourceLanguage::default(),
            new DataBag($data),
        );
    }
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\DateOccurrence;
use Atoolo\Resource\Resource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateOccurrence::class)]
class DateOccurrenceTest extends TestCase
{
    public function testFromResource(): void
    {
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => '/event?date=2858868000',
        ]);
        $this->assertEquals(
            2858868000,
            DateOccurrence::fromResource($resource)?->getTimestamp(),
            'start of the date',
        );
    }

    public function testFromResourceWithOtherParameters(): void
    {
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => '/event?id=4711&date=2858868000',
        ]);
        $this->assertEquals(
            2858868000,
            DateOccurrence::fromResource($resource)?->getTimestamp(),
            'start of the date next to other parameters',
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function urlsWithoutDateOccurrence(): array
    {
        return [
            'without query' => ['/event'],
            'without date' => ['/event?id=4711'],
            'empty date' => ['/event?date='],
            'date not a timestamp' => ['/event?date=2060-08-04'],
            'negative date' => ['/event?date=-1'],
            'date as array' => ['/event?date[]=2858868000'],
        ];
    }

    #[DataProvider('urlsWithoutDateOccurrence')]
    public function testFromResourceWithoutDateOccurrence(string $url): void
    {
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => $url,
        ]);
        $this->assertNull(DateOccurrence::fromResource($resource));
    }
}

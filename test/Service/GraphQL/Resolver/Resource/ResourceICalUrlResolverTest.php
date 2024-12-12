<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Factory\SchedulingFactory;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceICalUrlResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceSchedulingResolver;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceICalUrlResolver::class)]
class ResourceICalUrlResolverTest extends TestCase
{
    private ResourceICalUrlResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new ResourceICalUrlResolver();
    }

    public function testGetICalUrl(): void
    {
        $resource = $this->createResource([
            'url' => '/some/location',
        ]);
        $this->assertEquals(
            '/api/ical?location=%2Fsome%2Flocation',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlExternal(): void
    {
        $resource = $this->createResource([
            'id' => 'some_id',
            'url' => 'https://www.external.com/some/location',
        ]);
        $this->assertEquals(
            '/api/ical?id=some_id',
            $this->resolver->getICalUrl($resource),
        );
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

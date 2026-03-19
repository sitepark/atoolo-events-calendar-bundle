<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Factory\SchedulingFactory;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceICalUrlResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceSchedulingResolver;
use Atoolo\EventsCalendar\Test\TestResourceFactory;
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
        $resource = TestResourceFactory::create([
            'url' => '/some/location',
        ]);
        $this->assertEquals(
            '/api/ical/resource/some/location',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlWithLanguage(): void
    {
        $resource = TestResourceFactory::create([
            'url' => '/some/location',
            'locale' => 'en_US',
        ]);
        $this->assertEquals(
            '/api/ical/resource/en/some/location',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlExternal(): void
    {
        $resource = TestResourceFactory::create([
            'id' => 'some_id',
            'url' => 'https://www.external.com/some/location',
        ]);
        $this->assertNull($this->resolver->getICalUrl($resource));
    }
}

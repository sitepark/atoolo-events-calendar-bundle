<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceICalUrlResolver;
use Atoolo\Resource\Resource;
use PHPUnit\Framework\Attributes\CoversClass;
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
        $resource = Resource::create([
            'url' => '/some/location',
        ]);
        $this->assertEquals(
            '/api/ical/resource/some/location',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlWithLanguage(): void
    {
        $resource = Resource::create([
            'url' => '/some/location',
            'locale' => 'en_US',
        ]);
        $this->assertEquals(
            '/api/ical/resource/en/some/location',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlOfDateOccurrence(): void
    {
        $resource = Resource::create([
            'location' => '/event.php',
            'url' => '/event?date=2858868000',
        ]);
        $this->assertEquals(
            '/api/ical/resource/event.php?occurrence=2060-08-04T18%3A00%3A00%2B00%3A00',
            $this->resolver->getICalUrl($resource),
        );
    }

    public function testGetICalUrlExternal(): void
    {
        $resource = Resource::create([
            'id' => 'some_id',
            'url' => 'https://www.external.com/some/location',
        ]);
        $this->assertNull($this->resolver->getICalUrl($resource));
    }
}

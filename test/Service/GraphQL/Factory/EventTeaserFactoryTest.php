<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Factory;

use Atoolo\EventsCalendar\Service\GraphQL\Factory\EventTeaserFactory;
use Atoolo\GraphQL\Search\Factory\LinkFactory;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\Resource\Resource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventTeaserFactory::class)]
class EventTeaserFactoryTest extends TestCase
{
    private EventTeaserFactory $factory;

    private LinkFactory $linkFactory;

    public function setUp(): void
    {
        $this->linkFactory = $this->createStub(LinkFactory::class);
        $this->factory = new EventTeaserFactory(
            $this->linkFactory,
        );
    }

    public function testLink(): void
    {
        $resource = Resource::create([
            'url' => 'originalUrl',
        ]);
        $link = new Link('url');
        $this->linkFactory->method('create')
            ->willReturn($link);

        $teaser = $this->factory->create($resource);

        $this->assertEquals(
            $link,
            $teaser->link,
            'unexpected link',
        );
    }

    public function testHeadline(): void
    {

        $resource = Resource::create([
            'base' => [
                'teaser' => [
                    'headline' => 'Headline',
                ],
            ],
        ]);

        $teaser = $this->factory->create($resource);

        $this->assertEquals(
            'Headline',
            $teaser->headline,
            'unexpected headline',
        );
    }

    public function testHeadlineFallback(): void
    {
        $resource = Resource::create([
            'name' => 'ResourceName',
        ]);

        $teaser = $this->factory->create($resource);

        $this->assertEquals(
            'ResourceName',
            $teaser->headline,
            'unexpected headline',
        );
    }

    public function testText(): void
    {
        $resource = Resource::create([
            'base' => [
                'teaser' => [
                    'text' => 'Text',
                ],
            ],
        ]);

        $teaser = $this->factory->create($resource);

        $this->assertEquals(
            'Text',
            $teaser->text,
            'unexpected text',
        );
    }
}

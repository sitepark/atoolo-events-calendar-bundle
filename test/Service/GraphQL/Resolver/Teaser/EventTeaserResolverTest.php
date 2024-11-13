<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Teaser;

use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceEventDateResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Teaser\EventTeaserResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventTeaser;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceAssetResolver;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceKickerResolver;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceSymbolicAssetResolver;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\Resource\Resource;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventTeaserResolver::class)]
class EventTeaserResolverTest extends TestCase
{
    private EventTeaserResolver $resolver;

    private ResourceAssetResolver&MockObject $assetResolver;

    private ResourceSymbolicAssetResolver&MockObject $symbolicAssetResolver;

    private ResourceKickerResolver&MockObject $kickerResolver;

    private ResourceEventDateResolver&MockObject $eventDateResolver;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->assetResolver = $this->createMock(
            ResourceAssetResolver::class,
        );
        $this->symbolicAssetResolver = $this->createMock(
            ResourceSymbolicAssetResolver::class,
        );
        $this->kickerResolver = $this->createMock(
            ResourceKickerResolver::class,
        );
        $this->eventDateResolver = $this->createMock(
            ResourceEventDateResolver::class,
        );
        $this->resolver = new EventTeaserResolver(
            $this->assetResolver,
            $this->symbolicAssetResolver,
            $this->kickerResolver,
            $this->eventDateResolver,
        );
    }

    public function testGetUrl(): void
    {
        $url = '/some_url.php';
        $link = new Link($url);
        $teaser = new EventTeaser(
            $link,
            '',
            '',
            $this->createStub(Resource::class),
        );
        $this->assertEquals(
            $url,
            $this->resolver->getUrl($teaser),
            'getUrl should return the url of the teaser link',
        );
    }

    public function testGetAsset(): void
    {
        $this->assetResolver->expects($this->once())
            ->method('getAsset');
        $teaser = new EventTeaser(
            null,
            '',
            '',
            $this->createStub(Resource::class),
        );
        $args = $this->createStub(ArgumentInterface::class);

        $this->resolver->getAsset($teaser, $args);
    }

    public function testGetSymbolicAsset(): void
    {
        $this->symbolicAssetResolver->expects($this->once())
            ->method('getSymbolicAsset');
        $teaser = new EventTeaser(
            null,
            '',
            '',
            $this->createStub(Resource::class),
        );
        $args = $this->createStub(ArgumentInterface::class);

        $this->resolver->getSymbolicAsset($teaser, $args);
    }

    public function testGetKicker(): void
    {
        $this->kickerResolver->expects($this->once())
            ->method('getKicker');
        $teaser = new EventTeaser(
            null,
            '',
            '',
            $this->createStub(Resource::class),
        );
        $args = $this->createStub(ArgumentInterface::class);

        $this->resolver->getKicker($teaser, $args);
    }

    public function testGetEventDates(): void
    {
        $this->eventDateResolver->expects($this->once())
            ->method('getEventDates');
        $teaser = new EventTeaser(
            null,
            '',
            '',
            $this->createStub(Resource::class),
        );
        $args = $this->createStub(ArgumentInterface::class);

        $this->resolver->getEventDates($teaser, $args);
    }
}

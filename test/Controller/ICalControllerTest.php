<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\Scheduling;

use Atoolo\EventsCalendar\Controller\ICalController;
use Atoolo\EventsCalendar\Service\ICal\ICalFactory;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Resource\ResourceTenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function PHPUnit\Framework\once;

#[CoversClass(ICalController::class)]
class ICalControllerTest extends TestCase
{
    private ICalController $controller;

    private ResourceLoader&MockObject $resourceLoader;

    private ICalFactory&MockObject $iCalFactory;

    public function setUp(): void
    {
        $this->resourceLoader = $this->createMock(
            ResourceLoader::class,
        );
        $this->iCalFactory = $this->createMock(
            ICalFactory::class,
        );
        $resourceChannel = $this->createResourceChannel([
            'locale' => 'de_DE',
            'translationLocales' => ['en_US'],
        ]);
        $this->controller = new ICalController(
            $resourceChannel,
            $this->resourceLoader,
            $this->iCalFactory,
        );
    }

    public function testICalLocation(): void
    {
        $location = 'some/location';
        $resource = $this->createResource([
            'url' => $location,
        ]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php'))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->iCalByLocation($location);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            'text/calendar',
            $response->headers->get('Content-Type'),
        );
        $this->assertEquals(
            'Totally valid calendar data',
            $response->getContent(),
        );
    }

    public function testICalByLangAndLocation(): void
    {
        $location = 'some/location';
        $resource = $this->createResource([
            'url' => $location,
        ]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php'))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->iCalByLangAndLocation('de', $location);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            'text/calendar',
            $response->headers->get('Content-Type'),
        );
        $this->assertEquals(
            'Totally valid calendar data',
            $response->getContent(),
        );
    }

    public function testICalByLangAndLocationWithLanguage(): void
    {
        $lang = 'en';
        $location = 'some/location';
        $resource = $this->createResource([
            'url' => $location,
            'lang' => ResourceLanguage::of($lang),
        ]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php', ResourceLanguage::of($lang)))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->iCalByLangAndLocation($lang, $location);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            'text/calendar',
            $response->headers->get('Content-Type'),
        );
        $this->assertEquals(
            'Totally valid calendar data',
            $response->getContent(),
        );
    }

    public function testICalByLangAndLocationWithoutLanguage(): void
    {
        $locationA = 'some';
        $locationB = 'location';
        $location = $locationA . '/' . $locationB;
        $resource = $this->createResource([
            'url' => $location,
        ]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php'))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->iCalByLangAndLocation($locationA, $locationB);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            'text/calendar',
            $response->headers->get('Content-Type'),
        );
        $this->assertEquals(
            'Totally valid calendar data',
            $response->getContent(),
        );
    }

    public function testICalByLangAndLocationWithEmptyLanguage(): void
    {
        $locationA = '';
        $locationB = 'location';
        $location = $locationA . '/' . $locationB;
        $resource = $this->createResource([
            'url' => $location,
        ]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $locationB . '.php'))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->iCalByLangAndLocation($locationA, $locationB);
        $this->assertEquals(
            200,
            $response->getStatusCode(),
        );
        $this->assertEquals(
            'text/calendar',
            $response->headers->get('Content-Type'),
        );
        $this->assertEquals(
            'Totally valid calendar data',
            $response->getContent(),
        );
    }

    public function testICalByLangAndLocationNotFound(): void
    {
        $location = 'some/location';
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php'))
            ->willThrowException(
                new ResourceNotFoundException(ResourceLocation::of($location)),
            );
        $this->expectException(NotFoundHttpException::class);
        $this->controller->iCalByLangAndLocation('de', $location);
    }

    public function testICalByLangAndLocationInvalidResource(): void
    {
        $location = 'some/location';
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of('/' . $location . '.php'))
            ->willThrowException(
                new InvalidResourceException(ResourceLocation::of($location)),
            );
        $this->expectException(HttpException::class);
        $this->controller->iCalByLangAndLocation('de', $location);
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
            $data['lang'] ?? ResourceLanguage::default(),
            new DataBag($data),
        );
    }

    private function createResourceChannel(array $args): ResourceChannel
    {
        /** @var ResourceTenant $tenant */
        $tenant = $this->createStub(ResourceTenant::class);
        return new ResourceChannel(
            id: $args['id'] ?? '',
            name: $args['name'] ?? '',
            anchor: $args['anchor'] ?? '',
            serverName: $args['serverName'] ?? '',
            isPreview: $args['isPreview'] ?? false,
            nature: $args['nature'] ?? '',
            locale: $args['locale'] ?? '',
            baseDir: $args['baseDir'] ?? '',
            resourceDir: $args['resourceDir'] ?? '',
            configDir: $args['configDir'] ?? '',
            searchIndex: $args['searchIndex'] ?? '',
            translationLocales: $args['translationLocales'] ?? [],
            tenant: $tenant,
        );
    }
}

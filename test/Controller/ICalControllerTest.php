<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\Scheduling;

use Atoolo\EventsCalendar\Controller\ICalController;
use Atoolo\EventsCalendar\Service\ICal\ICalFactory;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Dto\Search\Query\Filter\IdFilter;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Search;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use function PHPUnit\Framework\once;

#[CoversClass(ICalController::class)]
class ICalControllerTest extends TestCase
{
    private ICalController $controller;

    private Search&MockObject $search;

    private ResourceLoader&MockObject $resourceLoader;

    private ICalFactory&MockObject $iCalFactory;

    public function setUp(): void
    {
        $this->search = $this->createMock(
            Search::class,
        );
        $this->resourceLoader = $this->createMock(
            ResourceLoader::class,
        );
        $this->iCalFactory = $this->createMock(
            ICalFactory::class,
        );
        $this->controller = new ICalController(
            $this->search,
            $this->resourceLoader,
            $this->iCalFactory,
        );
    }

    public function testCreateICalResponseMissingParams(): void
    {
        $response = $this->controller->createICalResponse(new Request());
        $this->assertEquals(
            400,
            $response->getStatusCode(),
        );
    }

    public function testCreateICalResponseByLocation(): void
    {
        $location = '/some/location';
        $resource = $this->createResource([
            'url' => $location,
        ]);
        $request = new Request(['location' => $location]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of($location))
            ->willReturn($resource);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->createICalResponse($request, );
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

    public function testCreateICalResponseByLocationNotFound(): void
    {
        $location = '/some/location';
        $request = new Request(['location' => $location]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of($location))
            ->willThrowException(
                new ResourceNotFoundException(ResourceLocation::of($location)),
            );
        $response = $this->controller->createICalResponse($request);
        $this->assertEquals(
            404,
            $response->getStatusCode(),
        );
    }

    public function testCreateICalResponseByLocationInvalidResource(): void
    {
        $location = '/some/location';
        $request = new Request(['location' => $location]);
        $this->resourceLoader
            ->expects(once())
            ->method('load')
            ->with(ResourceLocation::of($location))
            ->willThrowException(
                new InvalidResourceException(ResourceLocation::of($location)),
            );
        $response = $this->controller->createICalResponse($request);
        $this->assertEquals(
            500,
            $response->getStatusCode(),
        );
    }

    public function testCreateICalResponseById(): void
    {
        $id = 'some_id';
        $resource = $this->createResource([
            'id' => $id,
        ]);
        $request = new Request(['id' => $id]);
        $internalSearchResult = new SearchResult(1, 10, 0, [$resource], [], 1);
        $this->search
            ->expects(once())
            ->method('search')
            ->willReturn($internalSearchResult);
        $this->iCalFactory
            ->expects(once())
            ->method('createCalendarAsString')
            ->with($resource)
            ->willReturn('Totally valid calendar data');
        $response = $this->controller->createICalResponse($request, );
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

    public function testCreateICalResponseByIdNotFound(): void
    {
        $id = 'some_id';
        $resource = $this->createResource([
            'id' => $id,
        ]);
        $request = new Request(['id' => $id]);
        $internalSearchResult = new SearchResult(0, 10, 0, [], [], 1);
        $this->search
            ->expects(once())
            ->method('search')
            ->willReturn($internalSearchResult);
        $response = $this->controller->createICalResponse($request, );
        $this->assertEquals(
            404,
            $response->getStatusCode(),
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

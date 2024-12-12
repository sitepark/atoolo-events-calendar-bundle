<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Controller;

use Atoolo\EventsCalendar\Service\ICal\ICalFactory;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Dto\Search\Query\Filter\IdFilter;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Search;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ICalController extends AbstractController
{
    public function __construct(
        private readonly Search $search,
        private readonly ResourceLoader $loader,
        private readonly ICalFactory $iCalFactory,
    ) {}

    /**
     * @throws JsonException
     */
    #[Route('/api/ical', name: 'atoolo_events_calendar_ical')]
    public function createICalResponse(Request $request): Response
    {
        $location = $request->query->getString('location');
        if (!empty($location)) {
            return $this->createICalResponseByLocation(ResourceLocation::of($location));
        }
        $id = $request->query->getString('id');
        if (!empty($id)) {
            return $this->createICalResponseById($id);
        }
        $res = new JsonResponse(['error' => 'Either provide an \'id\' or a \'location\' of a resource']);
        $res->setStatusCode(400);
        return $res;
    }

    private function createICalResponseById(string $id): Response
    {
        $resource = $this->searchResourceById($id);
        if ($resource === null) {
            $res = new JsonResponse(['error' => 'resource with id ' . $id . ' not found']);
            $res->setStatusCode(404);
            return $res;
        }
        return $this->createICalResponeByResource($resource);
    }

    private function createICalResponseByLocation(ResourceLocation $location): Response
    {
        try {
            $resource = $this->loader->load($location);
        } catch (ResourceNotFoundException $e) {
            $res = new JsonResponse([
                'error' => 'resource at location ' . $location->__toString() . ' not found',
            ]);
            $res->setStatusCode(404);
            return $res;
        } catch (InvalidResourceException $e) {
            $res = new JsonResponse([
                'error' => 'resource at location ' . $location->__toString() . ' is invalid',
            ]);
            $res->setStatusCode(500);
            return $res;
        }
        return $this->createICalResponeByResource($resource);
    }

    private function createICalResponeByResource(Resource $resource): Response
    {
        $res = new Response($this->iCalFactory->createCalendarAsString($resource));
        $res->headers->set('Content-Type', 'text/calendar');
        return $res;
    }

    private function searchResourceById(string $id): ?Resource
    {
        $query = (new SearchQueryBuilder())
            ->filter(new IdFilter([$id]))
            ->build();
        $searchResult = $this->search->search($query);
        return  $searchResult->results[0] ?? null;
    }
}

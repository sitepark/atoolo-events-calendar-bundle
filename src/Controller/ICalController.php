<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Controller;

use Atoolo\EventsCalendar\Service\ICal\ICalFactory;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Search;
use JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ICalController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly ResourceLoader $loader,
        private readonly ICalFactory $iCalFactory,
        private readonly Search $search,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * @throws JsonException
     */
    #[Route(
        '/api/ical/resource/{location}',
        name: 'atoolo_events_calendar_ical_location',
        methods: ['GET'],
        requirements: ['location' => '.+'],
        format: 'json',
        priority: 1,
    )]
    public function iCalByLocation(string $location): Response
    {
        $resourceLocation = $this->toResourceLocation('', $location);
        return $this->createICalResponseByLocation($resourceLocation);
    }

    /**
     * @throws JsonException
     */
    #[Route(
        '/api/ical/resource/{lang}/{location}',
        name: 'atoolo_events_calendar_ical_lang_location',
        methods: ['GET'],
        requirements: ['location' => '.+'],
        format: 'json',
        priority: 2,
    )]
    public function iCalByLangAndLocation(string $lang, string $location): Response
    {
        $resourceLocation = $this->toResourceLocation($lang, $location);
        return $this->createICalResponseByLocation($resourceLocation);
    }

    /**
     * @throws JsonException
     */
    #[Route(
        '/api/ical/search/{query}',
        name: 'atoolo_events_calendar_ical_search',
        methods: ['GET'],
        requirements: ['query' => '.+'],
        format: 'json',
    )]
    public function iCalBySearch(string $query): Response
    {
        $searchQuery = $this->deserializeSearchQuery($query);
        return $this->createICalResponseBySearchQuery($searchQuery);
    }

    private function createICalResponseBySearchQuery(SearchQuery $searchQuery): Response
    {
        try {
            $resources = $this->search->search($searchQuery);
        } catch (\Throwable $th) {
            $this->logger?->warning(
                'Something went wrong while exectuing the search query',
                [
                    'searchQuery' => $searchQuery,
                    'exception' => $th,
                ],
            );
            throw new HttpException(500, 'Something went wrong while processing the search query', $th);
        }
        return $this->createICalResponeByResources(...$resources);
    }

    private function deserializeSearchQuery(string $query): SearchQuery
    {
        try {
            return $this->serializer->deserialize(
                $query,
                SearchQuery::class,
                'json',
            );
        } catch (\Throwable $th) {
            $this->logger?->warning(
                'Something went wrong while trying to deserialize a search query.',
                [
                    'serializer' => $this->serializer,
                    'query' => $query,
                    'exception' => $th,
                ],
            );
            throw new BadRequestHttpException('Invalid query \'' . $query . '\'', $th);
        }
    }

    private function createICalResponseByLocation(ResourceLocation $location): Response
    {
        try {
            $resource = $this->loader->load($location);
        } catch (ResourceNotFoundException $e) {
            throw new NotFoundHttpException('Resource at \'' . $location . '\' not found', $e);
        } catch (InvalidResourceException $e) {
            throw new HttpException(500, 'Resource at \'' . $location . '\' is invalid', $e);
        }
        return $this->createICalResponeByResources($resource);
    }

    private function createICalResponeByResources(Resource ...$resources): Response
    {
        $res = new Response($this->iCalFactory->createCalendarAsString(...$resources));
        $filename = 'ical';
        if (isset($resources[0])) {
            $sanitizedName = $this->sanitizeFilename($resources[0]->name);
            $filename = !empty($sanitizedName) ? $sanitizedName : $filename;
        }
        $res->headers->set('Content-Type', 'text/calendar');
        $res->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.ics"');
        return $res;
    }

    /**
     * Cuts off all sequences of non-alphanumeric characters at the beginning and the end of a string.
     * Replaces all remaining sequences of non-alphanumeric characters except '-' with '_'.
     *
     * Example: -?some()cr4zy=?"file-name9&& becomes some_cr4zy_file-name9
     */
    private function sanitizeFilename(string $originalName): string
    {
        $filename = preg_replace('/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/', '', $originalName);
        return preg_replace('/[^a-zA-Z0-9-]+/', '_', $filename ?? '') ?? '';
    }

    private function toResourceLocation(string $lang, string $path): ResourceLocation
    {
        $suffix = str_ends_with($path, '.php') ? '' : '.php';
        if ($this->isSupportedTranslation($lang)) {
            return ResourceLocation::of('/' . $path . $suffix, ResourceLanguage::of($lang));
        }

        if (str_starts_with($this->channel->locale, $lang . '_')) {
            return ResourceLocation::of('/' . $path . $suffix);
        }

        // lang is not a language but part of the path, if not empty
        $location = (
            empty($lang)
            ? '/' . $path
            : '/' . $lang . '/' . $path
        ) . $suffix;

        return ResourceLocation::of($location);
    }

    private function isSupportedTranslation(string $lang): bool
    {
        foreach ($this->channel->translationLocales as $locale) {
            if (str_starts_with($locale, $lang . '_')) {
                return true;
            }
        }
        return false;
    }
}

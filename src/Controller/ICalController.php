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
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ICalController extends AbstractController
{
    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly ResourceLoader $loader,
        private readonly ICalFactory $iCalFactory,
    ) {}

    /**
     * @throws JsonException
     */
    #[Route(
        '/api/ical/resource/{lang}/{location}',
        name: 'atoolo_events_calendar_ical_location',
        methods: ['GET'],
        requirements: ['location' => '.+'],
        format: 'json',
    )]
    public function iCalByLocation(string $lang, string $location): Response
    {
        $resourceLocation = $this->toResourceLocation($lang, $location);
        return $this->createICalResponseByLocation($resourceLocation);
    }

    private function createICalResponseByLocation(ResourceLocation $location): Response
    {
        try {
            $resource = $this->loader->load($location);
        } catch (ResourceNotFoundException $e) {
            throw new NotFoundHttpException('Resource at \'' . $location . '\' not found');
        } catch (InvalidResourceException $e) {
            throw new HttpException(500, 'Resource at \'' . $location . '\' is invalid');
        }
        return $this->createICalResponeByResources($resource);
    }

    private function createICalResponeByResources(Resource ...$resources): Response
    {
        $res = new Response($this->iCalFactory->createCalendarAsString(...$resources));
        $res->headers->set('Content-Type', 'text/calendar');
        return $res;
    }

    private function toResourceLocation(string $lang, string $path): ResourceLocation
    {
        if ($this->isSupportedTranslation($lang)) {
            return ResourceLocation::of('/' . $path . '.php', ResourceLanguage::of($lang));
        }

        if (str_starts_with($this->channel->locale, $lang . '_')) {
            return ResourceLocation::of('/' . $path . '.php');
        }

        // lang is not a language but part of the path, if not empty
        $location = (
            empty($lang)
            ? '/' . $path
            : '/' . $lang . '/' . $path
        ) . '.php';

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

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Teaser;

use Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource\ResourceEventDateResolver;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventDate;
use Atoolo\EventsCalendar\Service\GraphQL\Types\EventTeaser;
use Atoolo\GraphQL\Search\Resolver\Resolver;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceAssetResolver;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceKickerResolver;
use Atoolo\GraphQL\Search\Resolver\Resource\ResourceSymbolicImageResolver;
use Atoolo\GraphQL\Search\Types\Asset;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;

class EventTeaserResolver implements Resolver
{
    public function __construct(
        private readonly ResourceAssetResolver $assetResolver,
        private readonly ResourceSymbolicImageResolver $symbolicImageResolver,
        private readonly ResourceKickerResolver $kickerResolver,
        private readonly ResourceEventDateResolver $eventDateResolver,
    ) {}

    public function getUrl(
        EventTeaser $teaser,
    ): ?string {
        return $teaser->link?->url;
    }

    public function getKicker(
        EventTeaser $teaser,
    ): ?string {
        return $this->kickerResolver->getKicker($teaser->resource);
    }

    public function getAsset(
        EventTeaser $teaser,
        ArgumentInterface $args,
    ): ?Asset {
        return $this->assetResolver->getAsset($teaser->resource, $args);
    }

    public function getSymbolicImage(
        EventTeaser $teaser,
        ArgumentInterface $args,
    ): ?Asset {
        return $this->symbolicImageResolver
            ->getSymbolicImage($teaser->resource, $args);
    }

    /**
     * @return EventDate[]
     */
    public function getEventDates(
        EventTeaser $teaser,
        ArgumentInterface $args,
    ): array {
        return $this->eventDateResolver
            ->getEventDates($teaser->resource);
    }
}

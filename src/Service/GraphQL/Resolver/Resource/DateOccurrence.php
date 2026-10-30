<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\DefaultSchema2xDocumentEnricher;

/**
 * A hit of a single date (`searchEvents`) is the event resource with the
 * start of the date in its url (`?date=<timestamp>`).
 */
final class DateOccurrence
{
    public static function fromResource(Resource $resource): ?\DateTime
    {
        $query = parse_url($resource->url, PHP_URL_QUERY);
        if (!is_string($query)) {
            return null;
        }
        parse_str($query, $params);
        $start = $params[DefaultSchema2xDocumentEnricher::DATE_OCCURRENCE_PARAMETER] ?? null;
        if (!is_string($start) || !ctype_digit($start)) {
            return null;
        }
        return new \DateTime('@' . $start);
    }
}

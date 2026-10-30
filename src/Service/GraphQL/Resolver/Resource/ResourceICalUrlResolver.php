<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\Resource\Resource;

class ResourceICalUrlResolver
{
    /**
     * For a hit of a single date the iCal of this date only (`occurrence`).
     */
    public function getICalUrl(
        Resource $resource,
    ): ?string {
        $isExternal = str_starts_with($resource->location, 'http://')
            || str_starts_with($resource->location, 'https://');
        if ($isExternal) {
            return null;
        }
        $langCode = !empty($resource->lang->code)
            ? '/' . $resource->lang->code
            : '';
        $url = '/api/ical/resource' . $langCode . $resource->location;
        $occurrence = DateOccurrence::fromResource($resource);
        if ($occurrence !== null) {
            $url .= '?occurrence=' . rawurlencode($occurrence->format(\DateTimeInterface::ATOM));
        }
        return $url;
    }
}

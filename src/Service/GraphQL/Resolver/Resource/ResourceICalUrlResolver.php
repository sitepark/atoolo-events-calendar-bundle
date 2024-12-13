<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Resolver\Resource;

use Atoolo\Resource\Resource;

class ResourceICalUrlResolver
{
    public function getICalUrl(
        Resource $resource,
    ): ?string {
        $isExternal = str_starts_with($resource->location, 'http://')
            || str_starts_with($resource->location, 'https://');
        return $isExternal
            ? '/api/ical?id=' . urlencode($resource->id)
            : '/api/ical?location=' . urlencode($resource->location);
    }
}

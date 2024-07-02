<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\Indexer;

/**
 * @codeCoverageIgnore
 */
class RceEventIndexerParameter
{
    /**
     * @param int[] $groupPath
     * @param string[] $categoryRootResourceLocations
     */
    public function __construct(
        public readonly string $source,
        public readonly string $detailPageUrl,
        public readonly int $group,
        public readonly array $groupPath,
        public readonly array $categoryRootResourceLocations,
        public readonly int $cleanupThreshold,
        public readonly string $exportUrl,
    ) {}
}

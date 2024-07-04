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
     * @param array<RceEventIndexerInstance> $instanceList
     * @param array<string> $categoryRootResourceLocations
     */
    public function __construct(
        public readonly string $source,
        public readonly array $instanceList,
        public readonly array $categoryRootResourceLocations,
        public readonly int $cleanupThreshold,
        public readonly string $exportUrl,
    ) {}
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerPreset;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\IndexDocument;

/**
 * @template T of IndexDocument
 */
interface RceEventDocumentEnricher
{
    public function isIndexable(
        RceEventListItem $event,
        RceEventDate $eventDate,
    ): bool;

    /**
     * @param T $doc
     * @return T
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        RceEventIndexerPreset $preset,
        RceEventListItem $event,
        RceEventDate $eventDate,
        IndexDocument $doc,
        string $processId
    ): IndexDocument;
}

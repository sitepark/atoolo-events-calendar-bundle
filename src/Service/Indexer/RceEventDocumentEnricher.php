<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\IndexDocument;

/**
 * @template T of IndexDocument
 */
interface RceEventDocumentEnricher
{
    /**
     * @template E of T
     * @param E $doc
     * @return E
     */
    public function enrichDocument(
        RceEventIndexerParameter $parameter,
        RceEventListItem $event,
        RceEventDate $eventDate,
        IndexDocument $doc,
        string $processId
    ): IndexDocument;

    public function cleanup(): void;
}

<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerPreset;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\EventsCalendar\Service\RceEvent\RceEventListReader;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\Indexer\SolrIndexUpdater;
use Exception;
use Throwable;

class RceEventIndexer
{
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly IndexerProgressHandler $indexerProgressHandler,
        private readonly IndexingAborter $aborter,
        private readonly RceEventlistReader $rceEventListReader,
        private readonly SolrIndexService $indexService
    ) {
    }

    public function index(
        RceEventIndexerPreset $preset,
        RceEventIndexerParameter $parameter
    ): IndexerStatus {
        $this->rceEventListReader->read($parameter->rceEventListZip);

        $this->indexerProgressHandler->start($this->countEventDates());

        $updater = $this->indexService->updater();

        $processId = uniqid('', true);
        $successCount = 0;

        foreach ($this->rceEventListReader->getItems() as $rceEvent) {
            if (
                $this->aborter->shouldAborted($this->indexService->getIndex())
            ) {
                return $this->indexerProgressHandler->getStatus();
            }
            try {
                $successCount += $this->indexEvent(
                    $updater,
                    $preset,
                    $rceEvent,
                    $processId
                );
            } catch (Throwable $e) {
                $this->indexerProgressHandler->error($e);
            }
        }

        $result = $updater->update();
        if ($result->getStatus() !== 0) {
            $this->indexerProgressHandler->error(new Exception(
                $result->getResponse()->getStatusMessage()
            ));
            $this->indexerProgressHandler->getStatus();
        }

        if (
            $parameter->cleanupThreshold > 0 &&
            $successCount >= $parameter->cleanupThreshold
        ) {
            $this->indexService->deleteExcludingProcessId(
                null,
                $preset->source,
                $processId
            );
        }

        $this->indexService->commit(null);

        return $this->indexerProgressHandler->getStatus();
    }

    private function indexEvent(
        SolrIndexUpdater $updater,
        RceEventIndexerPreset $preset,
        RceEventListItem $event,
        string $processId
    ): int {

        $count = 0;
        foreach ($event->dates as $eventDate) {
            $count += $this->indexEventDate(
                $updater,
                $preset,
                $event,
                $eventDate,
                $processId
            );
        }
        return $count;
    }

    private function indexEventDate(
        SolrIndexUpdater $updater,
        RceEventIndexerPreset $preset,
        RceEventListItem $event,
        RceEventDate $eventDate,
        string $processId
    ): int {

        $this->indexerProgressHandler->advance(1);

        foreach ($this->documentEnricherList as $enricher) {
            if (!$enricher->isIndexable($event, $eventDate)) {
                $this->indexerProgressHandler->skip(1);
                continue;
            }
        }

        $count = 0;
        try {
            $doc = $updater->createDocument();
            foreach ($this->documentEnricherList as $enricher) {
                $doc = $enricher->enrichDocument(
                    $preset,
                    $event,
                    $eventDate,
                    $doc,
                    $processId
                );
            }

            $count++;
            $updater->addDocument($doc);
        } catch (Throwable $e) {
            $this->indexerProgressHandler->error($e);
        }

        return $count;
    }

    private function countEventDates(): int
    {
        $count = 0;
        foreach ($this->rceEventListReader->getItems() as $rceEvent) {
            $count += count($rceEvent->dates);
        }
        return $count;
    }

    public function abort(): void
    {
        $this->aborter->abort($this->indexService->getIndex());
    }
}

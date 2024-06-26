<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\EventsCalendar\Service\RceEvent\RceEventListReader;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\AbstractIndexer;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\Indexer\SolrIndexUpdater;
use Atoolo\Search\Service\IndexName;
use Exception;
use Throwable;

class RceEventIndexer extends AbstractIndexer
{
    /**
     * phpcs:ignore
     * @param iterable<RceEventDocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        IndexerProgressHandler $progressHandler,
        IndexingAborter $aborter,
        private readonly RceEventListReader $rceEventListReader,
        private readonly SolrIndexService $indexService,
        IndexName $index,
        IndexerConfigurationLoader $configLoader,
        string $source
    ) {
        parent::__construct(
            $index,
            $progressHandler,
            $aborter,
            $configLoader,
            $source
        );
    }

    public function index(): IndexerStatus
    {

        $parameter = $this->loadIndexerParameter();

        if (empty($parameter->exportUrl)) {
            return $this->progressHandler->getStatus();
        }

        $this->rceEventListReader->read($parameter->exportUrl);

        $this->progressHandler->start($this->countEventDates());

        $updater = $this->indexService->updater(ResourceLanguage::default());

        $processId = uniqid('', true);
        $successCount = 0;

        foreach ($this->rceEventListReader->getItems() as $rceEvent) {
            if (
                $this->isAbortionRequested()
            ) {
                return $this->progressHandler->getStatus();
            }
            $successCount += $this->indexEvent(
                $updater,
                $parameter,
                $rceEvent,
                $processId
            );
        }

        $result = $updater->update();
        if ($result->getStatus() !== 0) {
            $this->progressHandler->error(new Exception(
                $result->getResponse()->getStatusMessage()
            ));
            $this->progressHandler->getStatus();
        }

        if (
            $successCount >= $parameter->cleanupThreshold
        ) {
            $this->indexService->deleteExcludingProcessId(
                ResourceLanguage::default(),
                $this->source,
                $processId
            );
        }

        $this->indexService->commit(ResourceLanguage::default());

        $this->progressHandler->finish();
        gc_collect_cycles();

        return $this->progressHandler->getStatus();
    }

    private function loadIndexerParameter(): RceEventIndexerParameter
    {
        $config = $this->configLoader->load($this->source);
        $data = $config->data;

        /** @var array<int> $groupPath */
        $groupPath = $data->getArray('groupPath');

        /** @var array<string> $categoryRootResourceLocations */
        $categoryRootResourceLocations =
            $data->getArray('categoryRootResourceLocations');

        return new RceEventIndexerParameter(
            source: $this->source,
            detailPageUrl: $data->getString('detailPageUrl'),
            group: $data->getInt('group'),
            groupPath: $groupPath,
            categoryRootResourceLocations: $categoryRootResourceLocations,
            cleanupThreshold: $data->getInt('cleanupThreshold'),
            exportUrl: $data->getString('exportUrl')
        );
    }

    private function indexEvent(
        SolrIndexUpdater $updater,
        RceEventIndexerParameter $parameter,
        RceEventListItem $event,
        string $processId
    ): int {

        $count = 0;
        foreach ($event->dates as $eventDate) {
            $count += $this->indexEventDate(
                $updater,
                $parameter,
                $event,
                $eventDate,
                $processId
            );
        }
        return $count;
    }

    private function indexEventDate(
        SolrIndexUpdater $updater,
        RceEventIndexerParameter $parameter,
        RceEventListItem $event,
        RceEventDate $eventDate,
        string $processId
    ): int {

        $this->progressHandler->advance(1);

        $count = 0;
        try {
            /** @var IndexSchema2xDocument $doc */
            $doc = $updater->createDocument();
            foreach ($this->documentEnricherList as $enricher) {
                /** @var IndexSchema2xDocument $doc */
                $doc = $enricher->enrichDocument(
                    $parameter,
                    $event,
                    $eventDate,
                    $doc,
                    $processId
                );
            }

            $count++;
            $updater->addDocument($doc);
        } catch (Throwable $e) {
            $this->progressHandler->error($e);
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

    public function remove(array $idList): void
    {
        $this->indexService->deleteByIdListForAllLanguages(
            $this->source,
            $idList
        );
    }
}

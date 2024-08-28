<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;

class DefaultSchema2xEventDocumentEnricher implements DocumentEnricher
{
    /**
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {

        $items = $resource->data->getAssociativeArray('content.items');

        $main = $this->findInList($items, 'type', 'main');

        $venue = $this->findInList($main['items'] ?? [], 'id', 'eventsCalendar-venue');
        $doc = $this->addCategories($doc, $venue);

        $ticketAgency = $this->findInList($main['items'] ?? [], 'id', 'eventsCalendar-ticketAgency');
        $doc = $this->addCategories($doc, $ticketAgency);

        $organizer = $this->findInList($main['items'] ?? [], 'id', 'eventsCalendar-organizer');
        return $this->addCategories($doc, $organizer);
    }

    private function addCategories(IndexDocument $doc, array $item): IndexDocument
    {
        foreach ($item['model']['categories'] ?? [] as $category) {
            if (isset($category['id'])) {
                $doc->sp_category[] = (string) $category['id'];
            }
        }
        foreach ($item['model']['categoriesPath'] ?? [] as $categoryPath) {
            if (isset($categoryPath['id'])) {
                $doc->sp_category_path[] = (string) $categoryPath['id'];
            }
        }

        return $doc;
    }

    private function findInList(array $list, string $name, string $value): array
    {
        foreach ($list as $item) {
            if ($item[$name] === $value) {
                return $item;
            }
        }

        return [];
    }

    public function cleanup(): void {}
}

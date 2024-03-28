<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer\SiteKit;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventTheme;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\EventsCalendar\Service\Indexer\RceEventDocumentEnricher;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceHierarchyLoader;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;

/**
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class DefaultSchema2xRceEventDocumentEnricher implements
    RceEventDocumentEnricher
{
    /**
     * @var string[]
     */
    private array $supportedImageExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp'
    ];

    public function __construct(
        private readonly ResourceHierarchyLoader $categoryLoader,
    ) {
    }

    public function isIndexable(
        RceEventListItem $event,
        RceEventDate $eventDate
    ): bool {
        return true;
    }

    /**
     * @param IndexSchema2xDocument $doc
     * @return IndexSchema2xDocument
     */
    public function enrichDocument(
        RceEventIndexerParameter $parameter,
        RceEventListItem $event,
        RceEventDate $eventDate,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {

        $url = $parameter->detailPageUrl . '?id=' . $eventDate->hashId;

        $doc->id = $url;
        $doc->url = $url;
        $doc->sp_id = $parameter->id;
        $doc->sp_source = [$parameter->source];
        $doc->title = $event->name;
        $doc->sp_name = $event->name;
        $doc->sp_title = $event->name;
        $doc->sp_sortvalue = $event->name;
        $doc->description = $event->description;

        $doc->crawl_process_id = $processId;

        $doc->sp_group = $parameter->group;
        if (!empty($parameter->groupPath)) {
            $doc->sp_group_path = $parameter->groupPath;
        }

        if ($event->onsite) {
            $doc->setMetaBool('event_onlineEvent', true);
        }

        if (!empty($event->ticketLink)) {
            if ($event->onsite) {
                $doc->setMetaString('event_ticketLink', $event->ticketLink);
            } elseif ($event->online) {
                $doc->setMetaString('event_streamingLink', $event->ticketLink);
            }
        }

        $doc->sp_date = $eventDate->startDate;
        $doc->sp_changed = $eventDate->startDate;
        $doc->sp_date_from = $eventDate->startDate;
        $doc->sp_date_to = $eventDate->endDate;

        if ($eventDate->soldOut) {
            $doc->setMetaBool('event_soldout', true);
        }
        if ($eventDate->cancelled) {
            $doc->setMetaBool('event_cancelled', true);
        }

        if ($event->addresses->location !== null) {
            $doc->setMetaString(
                'event_location',
                $event->addresses->location->name
            );
            $doc->setMetaText(
                'event_rce_location',
                $event->addresses->location->name
            );
        }

        if ($event->addresses->organizer !== null) {
            $doc->setMetaText(
                'event_rce_organizer',
                $event->addresses->organizer->name
            );
        }

        if (!empty($event->keywords)) {
            $doc->keywords = [$event->keywords];
        }

        $content = [];
        if ($event->addresses->location !== null) {
            $content[] = $event->addresses->location->name;
            $content[] = $event->addresses->location->street;
            $content[] = $event->addresses->location->zip;
            $content[] = $event->addresses->location->city;
            $cleanContent = preg_replace(
                '/\s+/',
                ' ',
                implode(' ', $content)
            );
            $doc->content = $cleanContent;
        }

        $imagesUrls = [];
        foreach ($event->uploads as $upload) {
            if (!$this->isSupportedImage($upload->url)) {
                continue;
            }
            $imagesUrls[] = $upload->url;
        }
        $doc->setMetaString(
            'imageUrl',
            $imagesUrls
        );

        $doc->contenttype = 'text/html; charset=UTF-8';
        $spContentTypes = [
            'eventsCalendar-event',
            'schedule',
            'schedule_single',
            'schedule_start',
            'schedule_end'
        ];
        $doc->sp_contenttype = $spContentTypes;

        if ($event->theme !== null) {
            $doc->setMetaString('kicker', $event->theme->name);
        }

        $doc = $this->enrichCategories(
            $parameter,
            $event,
            $doc
        );

        return $doc;
    }

    public function enrichCategories(
        RceEventIndexerParameter $parameter,
        RceEventListItem $event,
        IndexDocument $doc
    ): IndexDocument {

        if ($event->theme === null) {
            return $doc;
        }

        if ($event->subTheme === null) {
            $doc = $this->enrichTheme(
                $parameter,
                $event->theme,
                $doc
            );
        } else {
            $doc = $this->enrichSubTheme(
                $parameter,
                $event->theme,
                $event->subTheme,
                $doc
            );
        }

        if ($event->highlight) {
            $doc = $this->enrichCategoryByAnchor(
                $doc,
                $parameter,
                'rce.type.highlight'
            );
        }

        if ($event->source !== null) {
            $doc = $this->enrichCategoryByAnchor(
                $doc,
                $parameter,
                'rce.source.' . $event->source->userId
            );
        }

        if (
            $event->addresses->location !== null &&
            $event->addresses->location->gemkey !== null
        ) {
            $doc = $this->enrichCategoryByAnchor(
                $doc,
                $parameter,
                'rce.gemkey.' . $event->addresses->location->gemkey
            );
        }

        if (
            $event->addresses->organizer !== null &&
            $event->addresses->organizer->gemkey !== null
        ) {
            $doc = $this->enrichCategoryByAnchor(
                $doc,
                $parameter,
                'rce.gemkey.' . $event->addresses->organizer->gemkey
            );
        }

        return $doc;
    }

    private function enrichTheme(
        RceEventIndexerParameter $preset,
        RceEventTheme $theme,
        IndexDocument $doc
    ): IndexDocument {

        $resource = $this->findCategoryByAnchor(
            $preset->categoryRootResourceLocations,
            'rce.type.' . $theme->getKey()
        );

        if ($resource === null) {
            return $doc;
        }

        $doc->sp_category = array_merge(
            $doc->sp_category ?? [],
            [$resource->getId()]
        );

        $doc = $this->enrichCategoryPath($doc, $resource);

        return $doc;
    }

    /**
     * Theme and subTheme are imported into infosite as categories.
     * There are two variants:
     *
     * - The SubTheme category is subordinate to the Theme category
     * - The SubTheme category is at the same level as the Theme category
     *
     * If the SubTheme category is subordinate to the Theme category,
     * the SubTheme category is indexed, otherwise the theme category
     * is also indexed.
     */
    private function enrichSubTheme(
        RceEventIndexerParameter $preset,
        RceEventTheme $theme,
        RceEventTheme $subTheme,
        IndexDocument $doc
    ): IndexDocument {

        $themeResource = $this->findCategoryByAnchor(
            $preset->categoryRootResourceLocations,
            'rce.type.' . $theme->getKey()
        );
        $subThemeResource = $this->findCategoryByAnchor(
            $preset->categoryRootResourceLocations,
            'rce.type.' . $subTheme->getKey()
        );

        if ($subThemeResource === null) {
            if ($themeResource !== null) {
                $doc = $this->enrichTheme(
                    $preset,
                    $theme,
                    $doc
                );
            }
            return $doc;
        }

        $doc->sp_category = array_merge(
            $doc->sp_category ?? [],
            [$subThemeResource->getId()]
        );

        $this->enrichCategoryPath($doc, $subThemeResource);

        if ($themeResource === null) {
            return $doc;
        }

        $parent = $this->categoryLoader->loadParent(
            $subThemeResource->getLocation()
        );
        if ($parent === null) {
            return $doc;
        }

        /*
         * If the theme category is not the parent of the subTheme
         * category, then also index this as a category.
         */
        if ($parent->getId() !== $themeResource->getId()) {
            $doc->sp_category = array_merge(
                $doc->sp_category ?? [],
                [$themeResource->getId()]
            );
            $doc = $this->enrichCategoryPath($doc, $themeResource);
        }

        return $doc;
    }

    private function enrichCategoryByAnchor(
        IndexDocument $doc,
        RceEventIndexerParameter $parameter,
        string $anchor
    ): IndexDocument {
        $category = $this->findCategoryByAnchor(
            $parameter->categoryRootResourceLocations,
            $anchor
        );

        if ($category === null) {
            return $doc;
        }

        $doc->sp_category = array_merge(
            $doc->sp_category ?? [],
            [$category->getId()]
        );

        $this->enrichCategoryPath($doc, $category);

        return $doc;
    }

    private function enrichCategoryPath(
        IndexDocument $doc,
        Resource $category
    ): IndexDocument {
        $path = $this->categoryLoader->loadPath($category->getLocation());
        $categoryPath = [];
        foreach ($path as $resource) {
            $categoryPath[] = $resource->getId();
        }
        $doc->sp_category_path = array_merge(
            $doc->sp_category_path ?? [],
            $categoryPath
        );
        return $doc;
    }

    /**
     * @param string[] $categoryRootResourceLocations
     * @param string $anchor
     * @return Resource|null
     */
    private function findCategoryByAnchor(
        array $categoryRootResourceLocations,
        string $anchor
    ): ?Resource {
        foreach ($categoryRootResourceLocations as $location) {
            $resource = $this->categoryLoader->findRecursive(
                $location,
                function ($resource) use ($anchor) {
                    $resourceAnchor =
                        $resource->getData()->getString('init.anchor');
                    return $resourceAnchor === $anchor;
                }
            );
            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }

    private function isSupportedImage($url): bool
    {
        $dotPosition = strrpos($url, '.');
        if ($dotPosition === false) {
            return false;
        }

        $suffix = substr($url, $dotPosition + 1);
        $suffix = strtolower($suffix);
        return in_array(
            $suffix,
            $this->supportedImageExtensions,
            true
        );
    }
}

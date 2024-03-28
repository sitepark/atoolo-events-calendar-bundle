<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\Indexer;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Service\Indexer\RceEventIndexerPresetLoader;
use Atoolo\Resource\ResourceBaseLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RceEventIndexerPresetLoader::class)]
class RceEventIndexerPresetLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $resourceLoaderLocator = $this->createStub(ResourceBaseLocator::class);
        $resourceLoaderLocator->method('locate')
            ->willReturn(
                __DIR__ .
                '/../../resources/RceEventIndexerPresetLoader'
            );
        $loader = new RceEventIndexerPresetLoader(
            $resourceLoaderLocator
        );

        $preset = $loader->load();

        $expected = new RceEventIndexerParameter(
            id: 'eventsCalendar-rceEvent-import',
            source: 'rce-event',
            detailPageUrl: '/rce-event.php',
            group: 4299,
            groupPath: [
                1002,
                1006,
                1086,
                1087,
                1097,
                4295,
                4298,
                4299
            ],
            categoryRootResourceLocations: [
                '/kategorien/themen/rce-veranstaltungsart.php',
                '/kategorien/gemeinde/rce-gemeinde.php',
                '/kategorien/quellen/rce-quellen.php'
            ],
            exportUrl: 'https://www.rce-event.de/export/uid573/rce-xml_neu.zip'
        );

        $this->assertEquals($expected, $preset, 'Preset does not match');
    }
}

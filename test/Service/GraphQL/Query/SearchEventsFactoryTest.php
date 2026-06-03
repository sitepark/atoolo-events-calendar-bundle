<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Query;

use Atoolo\EventsCalendar\Service\GraphQL\Query\SearchEventsFactory;
use Atoolo\GraphQL\Search\Input\SearchInput;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchEventsFactory::class)]
class SearchEventsFactoryTest extends TestCase
{
    private SearchEventsFactory $factory;

    public function setUp(): void
    {
        $this->factory = new SearchEventsFactory();
    }

    public function testCreate(): void
    {
        $searchInput = new SearchInput();
        $searchQuery = $this->factory->create($searchInput);

        $this->assertInstanceOf(SearchQuery::class, $searchQuery);
        $this->assertTrue($searchInput->expandByDate, 'expandByDate should be set to true');
    }
}

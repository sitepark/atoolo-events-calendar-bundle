<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Query;

use Atoolo\EventsCalendar\Service\GraphQL\Query\SearchEvents;
use Atoolo\GraphQL\Search\Input\SearchContextInput;
use Atoolo\GraphQL\Search\Input\SearchInput;
use Atoolo\GraphQL\Search\Query\Context\ContextDispatcher;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Search;
use Exception;
use Overblog\GraphQLBundle\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchEvents::class)]
class SearchEventsTest extends TestCase
{
    private SearchEvents $searchEvents;
    private Search $search;
    private ContextDispatcher $contextDispatcher;

    public function setUp(): void
    {
        $this->search = $this->createMock(Search::class);

        $this->contextDispatcher = $this->createMock(ContextDispatcher::class);
        $this->searchEvents = new SearchEvents(
            $this->search,
            $this->contextDispatcher,
        );
    }

    public function testSearchEventsWithoutContext(): void
    {
        $searchInput = new SearchInput();
        $searchInput->context = null;

        $expectedResult = new SearchResult(0, 10, 0, [], [], null, 0);

        $this->contextDispatcher
            ->expects($this->never())
            ->method('dispatch');
        $this->search
            ->expects($this->once())
            ->method('search')
            ->with($this->isInstanceOf(SearchQuery::class))
            ->willReturn($expectedResult);
        $result = $this->searchEvents->searchEvents($searchInput);

        $this->assertSame($expectedResult, $result);
    }

    public function testSearchEventsWithContext(): void
    {
        $searchInput = new SearchInput();
        $context = new SearchContextInput();
        $searchInput->context = $context;

        $expectedResult = new SearchResult(0, 10, 0, [], [], null, 0);

        $this->contextDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($context);
        $this->search
            ->expects($this->once())
            ->method('search')
            ->with($this->isInstanceOf(SearchQuery::class))
            ->willReturn($expectedResult);

        $result = $this->searchEvents->searchEvents($searchInput);
        $this->assertSame($expectedResult, $result);
    }

    public function testSearchEventsThrowsUserErrorOnException(): void
    {
        $searchInput = new SearchInput();

        $originalException = new Exception('Search failed');
        $this->search
            ->expects($this->once())
            ->method('search')
            ->with($this->isInstanceOf(SearchQuery::class))
            ->willThrowException($originalException);

        $this->expectException(UserError::class);
        $this->expectExceptionMessage('Search failed');

        $this->searchEvents->searchEvents($searchInput);
    }
}
<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Query;

use Atoolo\GraphQL\Search\Input\SearchInput;
use Atoolo\GraphQL\Search\Query\SearchQueryFactory;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Filter\ContentTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;

class SearchEventsFactory extends SearchQueryFactory
{
    public function create(SearchInput $input): SearchQuery
    {
        $builder = new SearchQueryBuilder();
        $input->expandByDate = true;

        return parent::create($input);
    }
}

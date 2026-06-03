<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL\Query;

use Atoolo\Events\Search\Types\Search\Input\Input\XSearchEventsInput;
use Atoolo\GraphQL\Search\Input\SearchInput;
use Atoolo\GraphQL\Search\Query\Context\ContextDispatcher;
use Exception;
use Overblog\GraphQLBundle\Annotation as GQL;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Overblog\GraphQLBundle\Error\UserError;

#[GQL\Provider]
class SearchEvents
{
    private readonly SearchEventsFactory $factory;

    public function __construct(
        private readonly \Atoolo\Search\Search $search,
        private readonly ContextDispatcher $contextDispatcher,
    ) {
        $this->factory = new SearchEventsFactory();
    }

    #[GQL\Query(name: 'searchEvents', type: 'SearchResult!')]
    public function searchEvents(SearchInput $input): SearchResult
    {
        if ($input->context !== null) {
            $this->contextDispatcher->dispatch($input->context);
        }
        $query = $this->factory->create($input);
        try {
            return $this->search->search($query);
        } catch (Exception $e) {
            throw new UserError(
                $e->getMessage(),
                0,
                $e,
            );
        }
    }
}

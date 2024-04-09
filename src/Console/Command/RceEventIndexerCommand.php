<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Console\Command;

use Atoolo\EventsCalendar\Service\Indexer\RceEventDocumentEnricher;
use Atoolo\EventsCalendar\Service\Indexer\RceEventIndexer;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rce-event:indexer',
    description: 'Fill a search index with rce-events'
)]
class RceEventIndexerCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly IndexerProgressBar $progressBar,
        private readonly RceEventIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $this->io = new SymfonyStyle($input, $output);

        $this->indexer->index();


        $this->errorReport($output);

        return Command::SUCCESS;
    }

    protected function errorReport(OutputInterface $output): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            if ($this->io->isVerbose() && $this->getApplication() !== null) {
                $this->getApplication()->renderThrowable($error, $output);
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }
}

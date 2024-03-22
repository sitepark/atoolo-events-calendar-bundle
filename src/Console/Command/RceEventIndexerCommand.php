<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Console\Command;

use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerParameter;
use Atoolo\EventsCalendar\Dto\Indexer\RceEventIndexerPreset;
use Atoolo\EventsCalendar\Service\Indexer\RceEventDocumentEnricher;
use Atoolo\EventsCalendar\Service\Indexer\RceEventIndexer;
use Atoolo\EventsCalendar\Service\RceEvent\RceEventListReader;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\IndexerProgressBarFactory;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rce-event:indexer',
    description: 'Fill a search index with rce-events'
)]
class RceEventIndexerCommand extends Command
{

    /**
     * phpcs:ignore
     * @param iterable<RceEventDocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly IndexerProgressBar $progressBar,
        private readonly RceEventIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to fill a search index')
            ->addArgument(
                'rce-event-list-zip',
                InputArgument::REQUIRED,
                'Url or path to the zip file containing all ' .
                'the rce events to be indexed.'
            )
            ->addOption(
                'cleanup-threshold',
                null,
                InputArgument::OPTIONAL,
                'The number of successfully determined events ' .
                    'required for the old ones to be removed.',
                500
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $typedInput = new TypifiedInput($input);
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $preset = new RceEventIndexerPreset(
            '8348',
            'rce-event',
            '/rce-event.php',
            345,
            [345],
            [
                '/kategorien/rce-veranstaltungskalender/veranstaltungsart/veranstaltungsart.php',
                '/kategorien/rce-veranstaltungskalender/gemeinde/gemeinde.php',
                '/kategorien/rce-veranstaltungskalender/quellen/quellen.php'
            ]
        );
        $params = new RceEventIndexerParameter(
            $typedInput->getStringArgument('rce-event-list-zip'),
            $typedInput->getIntOption('cleanup-threshold'),
        );

        $this->indexer->index($preset, $params);


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

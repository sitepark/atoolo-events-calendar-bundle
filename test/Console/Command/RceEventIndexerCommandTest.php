<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Console\Command;

use Atoolo\EventsCalendar\Console\Command\RceEventIndexerCommand;
use Atoolo\EventsCalendar\Service\Indexer\RceEventIndexer;
use Atoolo\Runtime\Check\Console\Command\CheckCommand;
use Atoolo\Runtime\Check\Service\Cli\RuntimeCheck;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(RceEventIndexerCommand::class)]
class RceEventIndexerCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private IndexerProgressBar $progressBar;

    private RceEventIndexer $indexer;

    public function setUp(): void
    {
        $this->progressBar = $this->createStub(
            IndexerProgressBar::class
        );

        $command = new RceEventIndexerCommand(
            $this->progressBar,
        );

        $this->commandTester = new CommandTester($command);
    }

}

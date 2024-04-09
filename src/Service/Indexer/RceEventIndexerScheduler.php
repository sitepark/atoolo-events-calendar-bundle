<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Atoolo\EventsCalendar\Dto\RceEvent\RceEventIndexEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
#[AsMessageHandler]
class RceEventIndexerScheduler implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private string $cron,
        private readonly RceEventIndexer $indexer,
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore()
        ),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::cron($this->cron, new RceEventIndexEvent()),
            )->lock($this->lockFactory->createLock(
                'rce-event-indexer-scheduler'
            ));
    }

    public function __invoke(RceEventIndexEvent $message): void
    {
        $status = $this->indexer->index();
        $this->logger->info("indexer finish: " . $status->getStatusLine());
    }
}

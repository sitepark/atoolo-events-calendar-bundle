<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Indexer;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

/**
 * If the application is redeployed, this is done by setting a symlink to
 * the newly installed version. The old project directory is still retained
 * to enable a rollback. After redeploying the application, all running
 * Massenger workers are invalid and must be stopped.
 *
 * To recognize this, a file is stored in the cache directory of the project
 * in which a random hash value is saved. The `WorkerRunningEvent` is used to
 * check whether the file still exists in the project cache directory and
 * whether it contains the expected hash value. If this is not the case, the
 * project directory has changed and the worker is stopped.
 *
 * The worker is restarted via a process manager such as
 * [Supervisor](http://supervisord.org/), which monitors the process and also
 * restarts it if the process has been stopped. The worker process is
 * then restarted for the newly deployed project.
 */
class StopWorkerOnRedeployListener implements EventSubscriberInterface
{
    private string $workerStartHashFile;
    private ?string $workerStartHash = null;

    public function __construct(
        string $cacheDir,
        private readonly LoggerInterface $logger,
    ) {
        $workerStartHashFile = realpath($cacheDir) .
            '/worker_start_hash_' .
            getmypid();
        if ($workerStartHashFile === false) {
            throw new RuntimeException(
                'Could not create worker start hash file'
            );
        }
        $this->workerStartHashFile = $workerStartHashFile;
    }

    public function onWorkerStarted(): void
    {
        $this->workerStartHash = bin2hex(random_bytes(18));
        $this->logger->info(
            "start redeploy listener with " .
            $this->workerStartHashFile . ' ' .
            'and hash ' . $this->workerStartHash
        );
        file_put_contents($this->workerStartHashFile, $this->workerStartHash);
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if (!$event->isWorkerIdle()) {
            return;
        }
        if (!$this->shouldStop()) {
            return;
        }

        $this->logger->info(
            'The project directory has changed. The worker is stopped.'
        );
        $event->getWorker()->stop();
    }

    private function shouldStop(): bool
    {
        if (!is_file($this->workerStartHashFile)) {
            return true;
        }

        $workerStartHash = file_get_contents($this->workerStartHashFile);
        return $workerStartHash !== $this->workerStartHash;
    }

    public static function getSubscribedEvents()
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStarted',
            WorkerRunningEvent::class => 'onWorkerRunning',
        ];
    }
}

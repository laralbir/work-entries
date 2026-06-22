<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Domain\WorkEntry\Event\WorkEntryClockedOutEvent;
use App\Domain\WorkEntry\Event\WorkEntryCreatedEvent;
use App\Domain\WorkEntry\Event\WorkEntryDeletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkEntryCreatedEvent::class, method: 'onCreated')]
#[AsEventListener(event: WorkEntryClockedOutEvent::class, method: 'onClockedOut')]
#[AsEventListener(event: WorkEntryDeletedEvent::class, method: 'onDeleted')]
final class WorkEntryEventListener
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function onCreated(WorkEntryCreatedEvent $event): void
    {
        $this->log('WorkEntry created', $event->workEntry);
    }

    public function onClockedOut(WorkEntryClockedOutEvent $event): void
    {
        $this->log('WorkEntry clocked out', $event->workEntry);
    }

    public function onDeleted(WorkEntryDeletedEvent $event): void
    {
        $this->log('WorkEntry deleted', $event->workEntry);
    }

    private function log(string $message, \App\Entity\WorkEntry $workEntry): void
    {
        $this->logger->info($message, [
            'workEntryId' => (string) $workEntry->getId(),
            'userId'      => (string) $workEntry->getUser()->getId(),
        ]);
    }
}

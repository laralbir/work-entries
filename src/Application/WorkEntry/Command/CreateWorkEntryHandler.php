<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Event\WorkEntryCreatedEvent;
use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateWorkEntryHandler
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $workEntryRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(CreateWorkEntryCommand $command): WorkEntry
    {
        $workEntry = new WorkEntry($command->user, $command->startDate, $command->endDate);

        $this->workEntryRepository->save($workEntry);
        $this->workEntryRepository->flush();

        $this->dispatcher->dispatch(new WorkEntryCreatedEvent($workEntry));

        return $workEntry;
    }
}

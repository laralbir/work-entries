<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Event\WorkEntryCreatedEvent;
use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class CreateWorkEntryHandler
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $workEntryRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(CreateWorkEntryCommand $command): WorkEntry
    {
        if ($this->workEntryRepository->findOverlapping($command->user, $command->startDate, $command->endDate) !== []) {
            throw new UnprocessableEntityHttpException('Work entry overlaps with an existing entry.');
        }

        $workEntry = new WorkEntry($command->user, $command->startDate, $command->endDate);

        $this->workEntryRepository->save($workEntry);
        $this->workEntryRepository->flush();

        $this->dispatcher->dispatch(new WorkEntryCreatedEvent($workEntry));

        return $workEntry;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Event\WorkEntryDeletedEvent;
use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteWorkEntryHandler
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $workEntryRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(DeleteWorkEntryCommand $command): void
    {
        $workEntry = $this->workEntryRepository->findById($command->workEntryId)
            ?? throw new NotFoundHttpException('WorkEntry not found.');

        $workEntry->softDelete();
        $this->workEntryRepository->flush();

        $this->dispatcher->dispatch(new WorkEntryDeletedEvent($workEntry));
    }
}

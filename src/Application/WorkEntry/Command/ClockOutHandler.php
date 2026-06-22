<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Event\WorkEntryClockedOutEvent;
use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ClockOutHandler
{
    public function __construct(
        private readonly WorkEntryRepositoryInterface $workEntryRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(ClockOutCommand $command): WorkEntry
    {
        $workEntry = $this->workEntryRepository->findById($command->workEntryId)
            ?? throw new NotFoundHttpException('WorkEntry not found.');

        if ($workEntry->getUser()->getId()->toRfc4122() !== $command->currentUser->getId()->toRfc4122()) {
            throw new AccessDeniedHttpException();
        }

        if ($workEntry->getEndDate() !== null) {
            throw new UnprocessableEntityHttpException('WorkEntry is already clocked out.');
        }

        $workEntry->setEndDate(new \DateTimeImmutable());
        $this->workEntryRepository->flush();

        $this->dispatcher->dispatch(new WorkEntryClockedOutEvent($workEntry));

        return $workEntry;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class UpdateWorkEntryHandler
{
    public function __construct(private readonly WorkEntryRepositoryInterface $workEntryRepository) {}

    public function __invoke(UpdateWorkEntryCommand $command): WorkEntry
    {
        $workEntry = $this->workEntryRepository->findById($command->workEntryId)
            ?? throw new NotFoundHttpException('WorkEntry not found.');

        if ($this->workEntryRepository->findOverlapping(
            $workEntry->getUser(),
            $command->startDate,
            $command->endDate,
            $command->workEntryId,
        ) !== []) {
            throw new UnprocessableEntityHttpException('Work entry overlaps with an existing entry.');
        }

        $workEntry->setStartDate($command->startDate);
        $workEntry->setEndDate($command->endDate);

        $this->workEntryRepository->flush();

        return $workEntry;
    }
}

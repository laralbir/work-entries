<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateWorkEntryHandler
{
    public function __construct(private readonly WorkEntryRepositoryInterface $workEntryRepository) {}

    public function __invoke(UpdateWorkEntryCommand $command): WorkEntry
    {
        $workEntry = $this->workEntryRepository->findById($command->workEntryId)
            ?? throw new NotFoundHttpException('WorkEntry not found.');

        $workEntry->setStartDate($command->startDate);
        $workEntry->setEndDate($command->endDate);

        $this->workEntryRepository->flush();

        return $workEntry;
    }
}

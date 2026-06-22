<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\WorkEntry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetWorkEntryHandler
{
    public function __construct(private readonly WorkEntryRepositoryInterface $workEntryRepository) {}

    public function __invoke(GetWorkEntryQuery $query): WorkEntry
    {
        return $this->workEntryRepository->findById($query->workEntryId)
            ?? throw new NotFoundHttpException('WorkEntry not found.');
    }
}

<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;

final class ListWorkEntriesHandler
{
    public function __construct(private readonly WorkEntryRepositoryInterface $workEntryRepository) {}

    /** @return \App\Entity\WorkEntry[] */
    public function __invoke(ListWorkEntriesQuery $query): array
    {
        return $this->workEntryRepository->findByUser($query->user);
    }
}

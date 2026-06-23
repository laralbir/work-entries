<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;

final class ListWorkEntriesHandler
{
    public function __construct(private readonly WorkEntryRepositoryInterface $workEntryRepository) {}

    public function __invoke(ListWorkEntriesQuery $query): WorkEntriesPage
    {
        $items = $this->workEntryRepository->findByUser(
            $query->user,
            $query->from,
            $query->to,
            $query->offset,
            $query->limit,
        );

        $total = $this->workEntryRepository->countByUser(
            $query->user,
            $query->from,
            $query->to,
        );

        return new WorkEntriesPage($items, $total);
    }
}

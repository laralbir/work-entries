<?php

declare(strict_types=1);

namespace App\Domain\WorkEntry\Repository;

use App\Entity\User;
use App\Entity\WorkEntry;
use Symfony\Component\Uid\Uuid;

interface WorkEntryRepositoryInterface
{
    public function findById(Uuid $id): ?WorkEntry;

    /**
     * @return WorkEntry[] ordered by startDate DESC
     */
    public function findByUser(
        User $user,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        int $offset = 0,
        int $limit = 20,
    ): array;

    public function countByUser(
        User $user,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): int;

    /**
     * Returns non-deleted entries for $user whose interval overlaps [$startDate, $endDate).
     * Pass $excludeId to skip a specific entry (useful when updating).
     *
     * @return WorkEntry[]
     */
    public function findOverlapping(
        User $user,
        \DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate,
        ?Uuid $excludeId = null,
    ): array;

    public function save(WorkEntry $workEntry): void;

    public function flush(): void;
}

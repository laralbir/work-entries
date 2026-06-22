<?php

declare(strict_types=1);

namespace App\Domain\WorkEntry\Repository;

use App\Entity\User;
use App\Entity\WorkEntry;
use Symfony\Component\Uid\Uuid;

interface WorkEntryRepositoryInterface
{
    public function findById(Uuid $id): ?WorkEntry;

    /** @return WorkEntry[] ordered by startDate DESC */
    public function findByUser(User $user): array;

    public function save(WorkEntry $workEntry): void;

    public function flush(): void;
}

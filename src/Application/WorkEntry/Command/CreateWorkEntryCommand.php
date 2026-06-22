<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Entity\User;

final readonly class CreateWorkEntryCommand
{
    public function __construct(
        public User $user,
        public \DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Entity\User;

final readonly class ListWorkEntriesQuery
{
    public function __construct(
        public User $user,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public int $offset = 0,
        public int $limit = 20,
    ) {}
}

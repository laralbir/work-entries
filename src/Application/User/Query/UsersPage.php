<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Entity\User;

final readonly class UsersPage
{
    /** @param User[] $items */
    public function __construct(
        public array $items,
        public int $totalItems,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Application\User\Query;

final readonly class ListUsersQuery
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public int $offset = 0,
        public int $limit = 20,
    ) {}
}

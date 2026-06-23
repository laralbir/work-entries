<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @return User[] ordered by name ASC
     */
    public function findAll(
        ?string $name = null,
        ?string $email = null,
        int $offset = 0,
        int $limit = 20,
    ): array;

    public function countAll(?string $name = null, ?string $email = null): int;

    public function save(User $user): void;

    public function flush(): void;
}

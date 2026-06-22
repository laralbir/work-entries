<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return User[] */
    public function findAll(): array;

    public function save(User $user): void;

    public function flush(): void;
}

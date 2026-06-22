<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\UserRepositoryInterface;

final class ListUsersHandler
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    /** @return \App\Entity\User[] */
    public function __invoke(ListUsersQuery $query): array
    {
        return $this->userRepository->findAll();
    }
}

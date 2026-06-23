<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\UserRepositoryInterface;

final class ListUsersHandler
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function __invoke(ListUsersQuery $query): UsersPage
    {
        $items = $this->userRepository->findAll(
            $query->name,
            $query->email,
            $query->offset,
            $query->limit,
        );

        $total = $this->userRepository->countAll($query->name, $query->email);

        return new UsersPage($items, $total);
    }
}

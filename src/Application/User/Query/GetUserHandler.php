<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserHandler
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function __invoke(GetUserQuery $query): User
    {
        return $this->userRepository->findById($query->userId)
            ?? throw new NotFoundHttpException('User not found.');
    }
}

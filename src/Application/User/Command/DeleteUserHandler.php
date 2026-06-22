<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteUserHandler
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId)
            ?? throw new NotFoundHttpException('User not found.');

        $user->softDelete();
        $this->userRepository->flush();
    }
}

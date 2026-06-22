<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function __invoke(UpdateUserCommand $command): User
    {
        $user = $this->userRepository->findById($command->userId)
            ?? throw new NotFoundHttpException('User not found.');

        $user->setName($command->name);
        $user->setEmail($command->email);

        if ($command->plainPassword !== null && $command->plainPassword !== '') {
            $user->setPassword($this->hasher->hashPassword($user, $command->plainPassword));
        }

        $this->userRepository->flush();

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(CreateUserCommand $command): User
    {
        $user = new User($command->name, $command->email);
        $user->setPassword($this->hasher->hashPassword($user, $command->plainPassword));

        $this->userRepository->save($user);
        $this->userRepository->flush();

        $this->dispatcher->dispatch(new UserCreatedEvent($user));

        return $user;
    }
}

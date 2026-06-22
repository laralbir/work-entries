<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\User\Command\UpdateUserCommand;
use App\Application\User\Command\UpdateUserHandler;
use App\Entity\User;

final class UserUpdateProcessor implements ProcessorInterface
{
    public function __construct(private readonly UpdateUserHandler $handler) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var User $data */
        return ($this->handler)(new UpdateUserCommand(
            userId: $data->getId(),
            name: $data->getName(),
            email: $data->getEmail(),
            plainPassword: $data->getPlainPassword(),
        ));
    }
}

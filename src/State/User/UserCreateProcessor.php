<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Entity\User;

final class UserCreateProcessor implements ProcessorInterface
{
    public function __construct(private readonly CreateUserHandler $handler) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var User $data */
        return ($this->handler)(new CreateUserCommand(
            name: $data->getName(),
            email: $data->getEmail(),
            plainPassword: $data->getPlainPassword() ?? '',
        ));
    }
}

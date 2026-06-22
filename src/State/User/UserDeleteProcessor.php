<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\User\Command\DeleteUserCommand;
use App\Application\User\Command\DeleteUserHandler;
use App\Entity\User;

final class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(private readonly DeleteUserHandler $handler) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var User $data */
        ($this->handler)(new DeleteUserCommand($data->getId()));

        return null;
    }
}

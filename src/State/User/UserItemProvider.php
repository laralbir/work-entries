<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\User\Query\GetUserHandler;
use App\Application\User\Query\GetUserQuery;

final class UserItemProvider implements ProviderInterface
{
    public function __construct(private readonly GetUserHandler $handler) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return ($this->handler)(new GetUserQuery($uriVariables['id']));
    }
}

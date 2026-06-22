<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\User\Query\ListUsersHandler;
use App\Application\User\Query\ListUsersQuery;

final class UserCollectionProvider implements ProviderInterface
{
    public function __construct(private readonly ListUsersHandler $handler) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return ($this->handler)(new ListUsersQuery());
    }
}

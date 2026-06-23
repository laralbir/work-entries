<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Application\User\Query\ListUsersHandler;
use App\Application\User\Query\ListUsersQuery;

final class UserCollectionProvider implements ProviderInterface
{
    public function __construct(private readonly ListUsersHandler $handler) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = $context['filters'] ?? [];

        $page  = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['itemsPerPage'] ?? $operation->getPaginationItemsPerPage() ?? 20)));

        $result = ($this->handler)(new ListUsersQuery(
            name:   $filters['name']  ?? null,
            email:  $filters['email'] ?? null,
            offset: ($page - 1) * $limit,
            limit:  $limit,
        ));

        return new TraversablePaginator(
            new \ArrayIterator($result->items),
            $page,
            $limit,
            $result->totalItems,
        );
    }
}

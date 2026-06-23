<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Application\WorkEntry\Query\ListWorkEntriesHandler;
use App\Application\WorkEntry\Query\ListWorkEntriesQuery;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class WorkEntryCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ListWorkEntriesHandler $handler,
        private readonly Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user    = $this->security->getUser();
        $filters = $context['filters'] ?? [];

        [$from, $to] = $this->parseDateFilters($filters);

        $page  = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['itemsPerPage'] ?? $operation->getPaginationItemsPerPage() ?? 20)));

        $result = ($this->handler)(new ListWorkEntriesQuery(
            user: $user,
            from: $from,
            to: $to,
            offset: ($page - 1) * $limit,
            limit: $limit,
        ));

        return new TraversablePaginator(
            new \ArrayIterator($result->items),
            $page,
            $limit,
            $result->totalItems,
        );
    }

    /** @return array{?\DateTimeImmutable, ?\DateTimeImmutable} */
    private function parseDateFilters(array $filters): array
    {
        try {
            $from = isset($filters['startDate']) ? new \DateTimeImmutable($filters['startDate']) : null;
            $to   = isset($filters['endDate'])   ? new \DateTimeImmutable($filters['endDate'])   : null;
        } catch (\Exception) {
            throw new BadRequestHttpException('Invalid date format. Use ISO 8601, e.g. 2026-06-01T00:00:00+00:00.');
        }

        return [$from, $to];
    }
}

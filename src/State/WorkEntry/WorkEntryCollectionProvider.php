<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\WorkEntry\Query\ListWorkEntriesHandler;
use App\Application\WorkEntry\Query\ListWorkEntriesQuery;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

final class WorkEntryCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ListWorkEntriesHandler $handler,
        private readonly Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return ($this->handler)(new ListWorkEntriesQuery($user));
    }
}

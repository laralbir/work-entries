<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\WorkEntry\Query\GetWorkEntryHandler;
use App\Application\WorkEntry\Query\GetWorkEntryQuery;

final class WorkEntryItemProvider implements ProviderInterface
{
    public function __construct(private readonly GetWorkEntryHandler $handler) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return ($this->handler)(new GetWorkEntryQuery($uriVariables['id']));
    }
}

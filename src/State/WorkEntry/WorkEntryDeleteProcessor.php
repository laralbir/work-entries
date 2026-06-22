<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\WorkEntry\Command\DeleteWorkEntryCommand;
use App\Application\WorkEntry\Command\DeleteWorkEntryHandler;
use App\Entity\WorkEntry;

final class WorkEntryDeleteProcessor implements ProcessorInterface
{
    public function __construct(private readonly DeleteWorkEntryHandler $handler) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var WorkEntry $data */
        ($this->handler)(new DeleteWorkEntryCommand($data->getId()));

        return null;
    }
}

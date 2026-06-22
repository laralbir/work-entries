<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\WorkEntry\Command\UpdateWorkEntryCommand;
use App\Application\WorkEntry\Command\UpdateWorkEntryHandler;
use App\Entity\WorkEntry;

final class WorkEntryUpdateProcessor implements ProcessorInterface
{
    public function __construct(private readonly UpdateWorkEntryHandler $handler) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WorkEntry
    {
        /** @var WorkEntry $data */
        return ($this->handler)(new UpdateWorkEntryCommand(
            workEntryId: $data->getId(),
            startDate: $data->getStartDate(),
            endDate: $data->getEndDate(),
        ));
    }
}

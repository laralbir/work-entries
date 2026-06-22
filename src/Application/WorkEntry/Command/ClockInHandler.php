<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Entity\WorkEntry;

final class ClockInHandler
{
    public function __construct(private readonly CreateWorkEntryHandler $createHandler) {}

    public function __invoke(ClockInCommand $command): WorkEntry
    {
        return ($this->createHandler)(new CreateWorkEntryCommand(
            user: $command->user,
            startDate: new \DateTimeImmutable(),
        ));
    }
}

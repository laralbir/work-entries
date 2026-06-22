<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteWorkEntryCommand
{
    public function __construct(public Uuid $workEntryId) {}
}

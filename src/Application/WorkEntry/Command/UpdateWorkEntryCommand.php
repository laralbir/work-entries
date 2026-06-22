<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateWorkEntryCommand
{
    public function __construct(
        public Uuid $workEntryId,
        public \DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate,
    ) {}
}

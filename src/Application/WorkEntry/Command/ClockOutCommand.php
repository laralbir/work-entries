<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Command;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

final readonly class ClockOutCommand
{
    public function __construct(
        public Uuid $workEntryId,
        public User $currentUser,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Entity\User;

final class UserCreatedEvent
{
    public function __construct(public readonly User $user) {}
}

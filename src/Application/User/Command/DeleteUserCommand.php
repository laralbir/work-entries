<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteUserCommand
{
    public function __construct(public Uuid $userId) {}
}

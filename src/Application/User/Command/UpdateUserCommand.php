<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateUserCommand
{
    public function __construct(
        public Uuid $userId,
        public string $name,
        public string $email,
        public ?string $plainPassword,
    ) {}
}

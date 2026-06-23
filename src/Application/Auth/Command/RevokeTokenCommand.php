<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class RevokeTokenCommand
{
    public function __construct(
        public string $jti,
        public \DateTimeImmutable $expiresAt,
    ) {}
}

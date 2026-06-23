<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Entity\RevokedToken;

interface RevokedTokenRepositoryInterface
{
    public function save(RevokedToken $token): void;

    public function flush(): void;

    public function isRevoked(string $jti): bool;
}

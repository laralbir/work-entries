<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\Auth\Repository\RevokedTokenRepositoryInterface;
use App\Entity\RevokedToken;

final class RevokeTokenHandler
{
    public function __construct(private readonly RevokedTokenRepositoryInterface $repository) {}

    public function __invoke(RevokeTokenCommand $command): void
    {
        if ($this->repository->isRevoked($command->jti)) {
            return;
        }

        $this->repository->save(new RevokedToken($command->jti, $command->expiresAt));
        $this->repository->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Auth;

use App\Domain\Auth\Repository\RevokedTokenRepositoryInterface;
use App\Entity\RevokedToken;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRevokedTokenRepository implements RevokedTokenRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(RevokedToken $token): void
    {
        $this->em->persist($token);
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    public function isRevoked(string $jti): bool
    {
        return $this->em->getRepository(RevokedToken::class)
            ->findOneBy(['jti' => $jti]) !== null;
    }
}

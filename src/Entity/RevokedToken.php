<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'revoked_tokens')]
#[ORM\Index(columns: ['expires_at'], name: 'IDX_RT_EXPIRES_AT')]
class RevokedToken
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $jti;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $revokedAt;

    public function __construct(string $jti, \DateTimeImmutable $expiresAt)
    {
        $this->jti       = $jti;
        $this->expiresAt = $expiresAt;
        $this->revokedAt = new \DateTimeImmutable();
    }

    public function getJti(): string { return $this->jti; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
}

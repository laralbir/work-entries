<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Auth\Repository\RevokedTokenRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: Events::JWT_DECODED)]
final class JwtDecodedListener
{
    public function __construct(private readonly RevokedTokenRepositoryInterface $repository) {}

    public function __invoke(JWTDecodedEvent $event): void
    {
        $jti = $event->getPayload()['jti'] ?? null;

        if ($jti !== null && $this->repository->isRevoked($jti)) {
            $event->markAsInvalid();
        }
    }
}

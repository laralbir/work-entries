<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: Events::JWT_CREATED)]
final class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $payload        = $event->getData();
        $payload['jti'] = Uuid::v7()->toRfc4122();
        $event->setData($payload);
    }
}

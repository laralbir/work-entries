<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Application\Auth\Command\RevokeTokenCommand;
use App\Application\Auth\Command\RevokeTokenHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class RevokeTokenController
{
    public function __construct(
        private readonly RevokeTokenHandler $handler,
        private readonly JWTEncoderInterface $encoder,
    ) {}

    public function __invoke(Request $request): Response
    {
        $authHeader = $request->headers->get('Authorization', '');
        $rawToken   = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';

        $payload = $this->encoder->decode($rawToken);

        $jti = $payload['jti'] ?? null;
        $exp = $payload['exp'] ?? null;

        if ($jti === null || $exp === null) {
            throw new BadRequestHttpException('Token is missing required claims (jti, exp).');
        }

        ($this->handler)(new RevokeTokenCommand(
            jti: $jti,
            expiresAt: new \DateTimeImmutable('@' . $exp),
        ));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class HomeController
{
    public function __construct(
        #[Autowire('%app.name%')] private readonly string $appName,
        #[Autowire('%app.version%')] private readonly string $appVersion,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'name'    => $this->appName,
            'version' => $this->appVersion,
        ]);
    }
}

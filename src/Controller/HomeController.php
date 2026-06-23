<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class HomeController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'name'    => 'Work Entries API',
            'version' => '1.1.3',
        ]);
    }
}

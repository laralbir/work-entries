<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[Autowire('%app.name%')] private string $appName,
        #[Autowire('%app.version%')] private string $appVersion,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        return $openApi
            ->withInfo(new Info(title: $this->appName, version: $this->appVersion))
            ->withSecurity([['JWT' => []]]);
    }
}

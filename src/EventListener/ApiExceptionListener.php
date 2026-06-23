<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        // Let API Platform / Security handle what they already cover with JSON
        if ($event->hasResponse()) {
            $contentType = $event->getResponse()->headers->get('Content-Type', '');
            if (str_contains($contentType, 'json')) {
                return;
            }
        }

        $exception  = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $event->setResponse(new JsonResponse([
            'status' => $statusCode,
            'detail' => $exception->getMessage(),
        ], $statusCode));

        // Prevent Symfony's HTML error renderer (priority -128) from overriding
        $event->stopPropagation();
    }
}

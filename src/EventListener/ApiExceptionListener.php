<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

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
        $statusCode = match (true) {
            $exception instanceof HttpExceptionInterface       => $exception->getStatusCode(),
            $exception instanceof SerializerExceptionInterface => 400,
            default                                            => 500,
        };

        $event->setResponse(new JsonResponse([
            'status' => $statusCode,
            'detail' => $this->resolveDetail($exception),
        ], $statusCode));

        // Prevent Symfony's HTML error renderer (priority -128) from overriding
        $event->stopPropagation();
    }

    private function resolveDetail(\Throwable $exception): string
    {
        $cause = $exception->getPrevious();

        if ($cause instanceof NotNormalizableValueException) {
            $path = $cause->getPath();

            return $path !== null && $path !== ''
                ? sprintf('"%s": %s', $path, $cause->getMessage())
                : $cause->getMessage();
        }

        return $exception->getMessage();
    }
}

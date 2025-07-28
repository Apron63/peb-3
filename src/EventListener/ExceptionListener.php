<?php

declare (strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        /** @disregard Undefined method 'getStatusCode' intelephense(P1013) */
        $exceptionStatusCode = $exception->getStatusCode();

        if (Response::HTTP_UNPROCESSABLE_ENTITY === $exceptionStatusCode) {
            $validationFailedException = $exception instanceof ValidationFailedException
                ? $exception
                : $exception->getPrevious();

            $errors = [];
            /** @disregard Undefined method 'getViolations' intelephense(P1013) */
            foreach ($validationFailedException->getViolations() as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse($errors, $exceptionStatusCode));
        }
    }
}

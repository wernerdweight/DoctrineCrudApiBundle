<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WernerDweight\DoctrineCrudApiBundle\Exception\ReturnableExceptionInterface;
use WernerDweight\RA\RA;

final class ExceptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['catchReturnableException', 10],
                ['catchAnyException', 0],
            ],
        ];
    }

    /**
     * @param ExceptionEvent $event
     */
    public function catchReturnableException(ExceptionEvent $event): void
    {
        $exception = $event->getException();
        if ($exception instanceof ReturnableExceptionInterface) {
            $event->setResponse(
                new JsonResponse(
                    $exception->getResponseData()->toArray(RA::RECURSIVE),
                    $exception->getStatusCode()
                )
            );
        }
    }

    /**
     * @param ExceptionEvent $event
     */
    public function catchAnyException(ExceptionEvent $event): void
    {
        $exception = $event->getException();
        $event->setResponse(
            new JsonResponse(
                ['reason' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            )
        );
    }
}

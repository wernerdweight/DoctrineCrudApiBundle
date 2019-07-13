<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use WernerDweight\DoctrineCrudApiBundle\Exception\ReturnableExceptionInterface;
use WernerDweight\RA\RA;

final class ExceptionEventSubscriber implements EventSubscriberInterface
{
    /** @var string */
    private const MESSAGE_KEY = 'message';
    /** @var string */
    private const ERROR_KEY = 'errors';
    /** @var string */
    private const GENERIC_ERROR_MESSAGE =
        'Request is not supported! Configuration is not allowing this kind of request, or it is not correct.';

    /** @var LoggerInterface */
    private $logger;

    /**
     * ExceptionEventSubscriber constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
            $responseData = $exception->getResponseData()->toArray(RA::RECURSIVE);
            $event->setResponse(
                new JsonResponse(
                    [
                        self::MESSAGE_KEY => $exception->getMessage(),
                        self::ERROR_KEY => $responseData,
                    ],
                    $exception->getStatusCode()
                )
            );
            $this->logger->debug($exception->getMessage(), $responseData);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(
                new JsonResponse(
                    [self::MESSAGE_KEY => $exception->getMessage()],
                    $exception->getStatusCode()
                )
            );
            $this->logger->debug($exception->getMessage(), $exception->getTrace());
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
                [self::MESSAGE_KEY => self::GENERIC_ERROR_MESSAGE],
                Response::HTTP_BAD_REQUEST
            )
        );
        $this->logger->error($exception->getMessage(), $exception->getTrace());
    }
}

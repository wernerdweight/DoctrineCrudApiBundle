<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;
use WernerDweight\RA\RA;

abstract class AbstractSimpleReturnableException extends AbstractEnhancedException implements ReturnableExceptionInterface
{
    public function getResponseData(): RA
    {
        return new RA();
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

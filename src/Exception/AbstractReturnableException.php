<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;
use WernerDweight\RA\RA;

abstract class AbstractReturnableException extends AbstractEnhancedException implements ReturnableExceptionInterface
{
    /**
     * @var RA
     */
    private $responseData;

    /**
     * ItemValidatorReturnableException constructor.
     *
     * @param mixed[] $payload
     */
    public function __construct(int $code, array $payload = [], ?\Throwable $previous = null)
    {
        parent::__construct($code, [], $previous);
        $this->responseData = new RA($payload, RA::RECURSIVE);
    }

    public function getResponseData(): RA
    {
        return $this->responseData;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

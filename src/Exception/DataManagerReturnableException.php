<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class DataManagerReturnableException extends AbstractReturnableException
{
    /** @var int */
    public const UNKNOWN_ENTITY_REQUESTED = 1;

    /** @var string[] */
    protected static $messages = [
        self::UNKNOWN_ENTITY_REQUESTED => 'The requested item doesn\'t exist!',
    ];

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}

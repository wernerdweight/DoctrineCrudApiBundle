<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class InvalidRequestException extends AbstractEnhancedException
{
    /**
     * @var int
     */
    public const EXCEPTION_NO_REQUEST = 1;

    /**
     * @var int
     */
    public const EXCEPTION_NO_ENTITY_NAME = 2;

    /**
     * @var int
     */
    public const EXCEPTION_INVALID_FILTERING_ENTITY = 3;

    /**
     * @var string[]
     */
    protected static $messages = [
        self::EXCEPTION_NO_REQUEST => 'No request has been recieved!',
        self::EXCEPTION_NO_ENTITY_NAME => 'No entity name specified in request!',
        self::EXCEPTION_INVALID_FILTERING_ENTITY => 'Invalid entity %s requested for filtering! ' .
            'Please, make sure that the entity exists and implements ApiEntityInterface.',
    ];
}

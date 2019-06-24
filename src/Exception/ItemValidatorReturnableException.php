<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

class ItemValidatorReturnableException extends AbstractReturnableException
{
    /** @var int */
    public const INVALID_ITEM = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_ITEM => 'Item is not valid!',
    ];
}

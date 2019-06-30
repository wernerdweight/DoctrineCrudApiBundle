<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

class UpdaterReturnableException extends AbstractReturnableException
{
    /** @var int */
    public const INVALID_NESTING = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_NESTING => 'Can\'t update nested entity!',
    ];
}

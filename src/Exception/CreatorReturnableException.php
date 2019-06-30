<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolation;
use WernerDweight\RA\RA;

class CreatorReturnableException extends AbstractReturnableException
{
    /** @var int */
    public const INVALID_NESTING = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_NESTING => 'Can\'t create nested entity!',
    ];
}

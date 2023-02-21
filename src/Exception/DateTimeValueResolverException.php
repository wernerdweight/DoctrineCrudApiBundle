<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class DateTimeValueResolverException extends AbstractEnhancedException
{
    /**
     * @var int
     */
    public const INVALID_VALUE = 1;

    /**
     * @var string[]
     */
    protected static $messages = [
        self::INVALID_VALUE => 'Invalid date or datetime value!',
    ];
}

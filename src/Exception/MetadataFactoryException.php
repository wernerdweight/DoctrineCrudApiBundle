<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class MetadataFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const UNEXPECTED_DRIVER = 1;
    /** @var int */
    public const UNKNOWN_DEFAULT_DRIVER_IMPLEMENTATION = 2;

    /** @var string[] */
    protected static $messages = [
        self::UNEXPECTED_DRIVER => 'Driver %s was not expected!',
        self::UNKNOWN_DEFAULT_DRIVER_IMPLEMENTATION => 'Default driver implementation is not known',
    ];
}

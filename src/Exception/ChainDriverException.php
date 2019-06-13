<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class ChainDriverException extends AbstractEnhancedException
{
    /** @var int */
    public const NO_READER_NEEDED = 1;
    /** @var int */
    public const NO_LOCATOR_NEEDED = 2;
    /** @var int */
    public const NO_DRIVER_FOR_ENTITY = 3;

    /** @var string[] */
    protected static $messages = [
        self::NO_READER_NEEDED => 'No reader is needed for chain driver!',
        self::NO_LOCATOR_NEEDED => 'No locator is needed for chain driver!',
        self::NO_DRIVER_FOR_ENTITY => 'No driver exists for entity %s!',
    ];
}

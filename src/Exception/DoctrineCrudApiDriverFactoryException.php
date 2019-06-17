<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class DoctrineCrudApiDriverFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const INVALID_DRIVER_TYPE = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_DRIVER_TYPE => 'No driver for type %s found! Make sure your repository implements DoctrineCrudApiDriverInterface',
    ];
}

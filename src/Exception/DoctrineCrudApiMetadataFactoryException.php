<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class DoctrineCrudApiMetadataFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const UNEXPECTED_DRIVER = 1;

    /** @var string[] */
    protected static $messages = [
        self::UNEXPECTED_DRIVER => 'Driver %s was not expected!',
    ];
}

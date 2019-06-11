<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class ConfigurationManagerException extends AbstractEnhancedException
{
    /** @var int */
    public const EXCEPTION_NO_CONFIGURATION_FOR_ENTITY = 1;

    /** @var string[] */
    protected static $messages = [
        self::EXCEPTION_NO_CONFIGURATION_FOR_ENTITY => 'No configuration found for entity %s!',
    ];
}

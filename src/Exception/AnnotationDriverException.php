<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class AnnotationDriverException extends AbstractEnhancedException
{
    /** @var int */
    public const NO_LOCATOR_NEEDED = 1;
    /** @var int */
    public const NO_CONFIGURATION_NEEDED_FOR_ACCESSIBLE = 2;

    /** @var string[] */
    protected static $messages = [
        self::NO_LOCATOR_NEEDED => 'No locator is needed for annotation driver!',
        self::NO_CONFIGURATION_NEEDED_FOR_ACCESSIBLE => 'No configuration should be parsed for accessible annotation!',
    ];
}

<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class AnnotationDriverException extends AbstractEnhancedException
{
    /** @var int */
    public const NO_LOCATOR_NEEDED = 1;

    /** @var string[] */
    protected static $messages = [
        self::NO_LOCATOR_NEEDED => 'No locator is needed for annotation driver!',
    ];
}

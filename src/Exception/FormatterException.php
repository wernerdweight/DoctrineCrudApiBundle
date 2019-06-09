<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class FormatterException extends AbstractEnhancedException
{
    /** @var int */
    public const EXCEPTION_INVALID_AGGREGATE_FIELD_NAME = 1;

    /** @var string[] */
    protected static $messages = [
        self::EXCEPTION_INVALID_AGGREGATE_FIELD_NAME => '%s is not a valid aggregate field name!',
    ];
}

<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class FormatterException extends AbstractEnhancedException
{
    /** @var int */
    public const EXCEPTION_INVALID_AGGREGATE_FIELD_NAME = 1;
    /** @var int */
    public const EXCEPTION_NO_PROPERTY_GETTER = 2;
    /** @var int */
    public const EXCEPTION_INVALID_METADATA_TYPE = 3;

    /** @var string[] */
    protected static $messages = [
        self::EXCEPTION_INVALID_AGGREGATE_FIELD_NAME => '%s is not a valid aggregate field name!',
        self::EXCEPTION_NO_PROPERTY_GETTER => 'No getter method was found and the property is not public or does not exist for field %s of entity %s!',
        self::EXCEPTION_INVALID_METADATA_TYPE => '%s is not a valid metadata type!',
    ];
}

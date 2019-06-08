<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class MappingResolverException extends AbstractEnhancedException
{
    /** @var int */
    public const EXCEPTION_UNKNOWN_MAPPING_TYPE = 1;
    /** @var int */
    public const EXCEPTION_MISSING_MAPPING_TYPE = 2;
    /** @var int */
    public const EXCEPTION_MISSING_TARGET_ENTITY = 3;
    /** @var int */
    public const EXCEPTION_UNKNOWN_RELATED_ENTITY = 4;

    /** @var string[] */
    protected static $messages = [
        self::EXCEPTION_UNKNOWN_MAPPING_TYPE => '%s is not a valid mapping type!',
        self::EXCEPTION_MISSING_MAPPING_TYPE => 'Mapping type is missing from entity mapping!',
        self::EXCEPTION_MISSING_TARGET_ENTITY => 'Mapping type %s requires target entity to be specified!',
        self::EXCEPTION_UNKNOWN_RELATED_ENTITY => 'Entity %s with id %s does not exist!',
    ];
}

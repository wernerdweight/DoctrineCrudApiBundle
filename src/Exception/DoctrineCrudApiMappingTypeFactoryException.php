<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class DoctrineCrudApiMappingTypeFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const INVALID_MAPPING_TYPE = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_MAPPING_TYPE => 'No mapping processor for type %s found!',
    ];
}

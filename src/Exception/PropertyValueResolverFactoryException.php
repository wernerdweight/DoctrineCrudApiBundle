<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class PropertyValueResolverFactoryException extends AbstractEnhancedException
{
    /**
     * @var int
     */
    public const INVALID_PROPERTY_TYPE = 1;

    /**
     * @var string[]
     */
    protected static $messages = [
        self::INVALID_PROPERTY_TYPE => 'No property value resolver for type %s found!',
    ];
}

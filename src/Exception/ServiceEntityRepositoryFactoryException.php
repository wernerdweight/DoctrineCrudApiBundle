<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class ServiceEntityRepositoryFactoryException extends AbstractEnhancedException
{
    /** @var int */
    public const INVALID_ENTITY_CLASS = 1;

    /** @var string[] */
    protected static $messages = [
        self::INVALID_ENTITY_CLASS => 'No repository for entity %s found! Make sure your repository implements ServiceEntityRepositoryInterface',
    ];
}

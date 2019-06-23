<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

use WernerDweight\EnhancedException\Exception\AbstractEnhancedException;

class DataManagerException extends AbstractEnhancedException
{
    /** @var int */
    public const UNKNOWN_ENTITY_REQUESTED = 1;

    /** @var string[] */
    protected static $messages = [
        self::UNKNOWN_ENTITY_REQUESTED => 'The requested entity doesn\'t exist!',
    ];
}

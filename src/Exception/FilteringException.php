<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Exception;

class FilteringException extends AbstractSimpleReturnableException
{
    /**
     * @var int
     */
    public const EXCEPTION_INVALID_FILTER_LOGIC = 1;

    /**
     * @var int
     */
    public const EXCEPTION_MISSING_FILTER_FIELD = 2;

    /**
     * @var int
     */
    public const EXCEPTION_INVALID_FILTER_OPERATOR = 3;

    /**
     * @var int
     */
    public const EXCEPTION_INVALID_ORDERING_DIRECTION = 4;

    /**
     * @var int
     */
    public const EXCEPTION_INVALID_AGGREGATE_FUNCTION = 5;

    /**
     * @var int
     */
    public const EXCEPTION_INVALID_CONDITION = 6;

    /**
     * @var string[]
     */
    protected static $messages = [
        self::EXCEPTION_INVALID_FILTER_LOGIC => '%s is not a valid filtering logic! Use one of %s.',
        self::EXCEPTION_MISSING_FILTER_FIELD => 'Filtering field is missing!',
        self::EXCEPTION_INVALID_FILTER_OPERATOR => '%s is not a valid filtering operator! Use one of %s.',
        self::EXCEPTION_INVALID_ORDERING_DIRECTION => '%s is not a valid ordering direction! Use one of %s.',
        self::EXCEPTION_INVALID_AGGREGATE_FUNCTION => '%s is not a valid aggregate function! Use one of %s.',
        self::EXCEPTION_INVALID_CONDITION => 'Invalid filtering condition!',
    ];
}

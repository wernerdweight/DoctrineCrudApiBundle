<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

class ParameterEnum
{
    /** @var string */
    public const OFFSET = 'offset';
    /** @var string */
    public const LIMIT = 'limit';
    /** @var string */
    public const FILTER = 'filter';
    /** @var string */
    public const ORDER_BY = 'orderBy';
    /** @var string */
    public const GROUP_BY = 'groupBy';
    /** @var string */
    public const RESPONSE_STRUCTURE = 'responseStructure';
}

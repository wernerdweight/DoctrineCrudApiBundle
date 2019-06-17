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
    public const FILTER_CONDITIONS = 'conditions';
    /** @var string */
    public const FILTER_LOGIC = 'logic';
    /** @var string */
    public const FILTER_LOGIC_AND = 'and';
    /** @var string */
    public const FILTER_LOGIC_OR = 'or';
    /** @var string[] */
    public const AVAILABLE_FILTERING_LOGICS = [
        self::FILTER_LOGIC_AND,
        self::FILTER_LOGIC_OR,
    ];
    /** @var string */
    public const FILTER_FIELD = 'field';
    /** @var string */
    public const FILTER_FIELD_SEPARATOR = '.';
    /** @var string */
    public const FILTER_OPERATOR = 'operator';
    /** @var string */
    public const FILTER_OPERATOR_EQUAL = 'eq';
    /** @var string */
    public const FILTER_OPERATOR_NOT_EQUAL = 'neq';
    /** @var string */
    public const FILTER_OPERATOR_GREATER_THAN = 'gt';
    /** @var string */
    public const FILTER_OPERATOR_GREATER_THAN_OR_EQUAL = 'gte';
    /** @var string */
    public const FILTER_OPERATOR_GREATER_THAN_OR_EQUAL_OR_NULL = 'gten';
    /** @var string */
    public const FILTER_OPERATOR_LOWER_THAN = 'lt';
    /** @var string */
    public const FILTER_OPERATOR_LOWER_THAN_OR_EQUAL = 'lte';
    /** @var string */
    public const FILTER_OPERATOR_BEGINS_WITH = 'begins';
    /** @var string */
    public const FILTER_OPERATOR_CONTAINS = 'contains';
    /** @var string */
    public const FILTER_OPERATOR_CONTAINS_NOT = 'not-contains';
    /** @var string */
    public const FILTER_OPERATOR_ENDS_WITH = 'ends';
    /** @var string */
    public const FILTER_OPERATOR_IS_NULL = 'null';
    /** @var string */
    public const FILTER_OPERATOR_IS_NOT_NULL = 'not-null';
    /** @var string */
    public const FILTER_OPERATOR_IS_EMPTY = 'empty';
    /** @var string */
    public const FILTER_OPERATOR_IS_NOT_EMPTY = 'not-empty';
    /** @var string */
    public const FILTER_OPERATOR_IN = 'in';
    /** @var string[] */
    public const AVAILABLE_FILTERING_OPERATORS = [
        self::FILTER_OPERATOR_EQUAL,
        self::FILTER_OPERATOR_NOT_EQUAL,
        self::FILTER_OPERATOR_GREATER_THAN,
        self::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL,
        self::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL_OR_NULL,
        self::FILTER_OPERATOR_LOWER_THAN,
        self::FILTER_OPERATOR_LOWER_THAN_OR_EQUAL,
        self::FILTER_OPERATOR_BEGINS_WITH,
        self::FILTER_OPERATOR_CONTAINS,
        self::FILTER_OPERATOR_CONTAINS_NOT,
        self::FILTER_OPERATOR_ENDS_WITH,
        self::FILTER_OPERATOR_IS_NULL,
        self::FILTER_OPERATOR_IS_NOT_NULL,
        self::FILTER_OPERATOR_IS_EMPTY,
        self::FILTER_OPERATOR_IS_NOT_EMPTY,
        self::FILTER_OPERATOR_IN,
    ];
    /** @var string */
    public const FILTER_VALUE = 'value';
    /** @var string */
    public const FILTER_VALUE_WILDCARD = '*';

    /** @var string */
    public const ORDER_BY = 'orderBy';
    /** @var string */
    public const ORDER_BY_FIELD = 'field';
    /** @var string */
    public const ORDER_BY_DIRECTION = 'direction';
    /** @var string */
    public const ORDER_BY_DIRECTION_ASC = 'asc';
    /** @var string */
    public const ORDER_BY_DIRECTION_DESC = 'desc';
    /** @var string[] */
    public const AVAILABLE_ORDERING_DIRECTIONS = [
        self::ORDER_BY_DIRECTION_ASC,
        self::ORDER_BY_DIRECTION_DESC,
    ];

    /** @var string */
    public const GROUP_BY = 'groupBy';
    /** @var string */
    public const GROUP_BY_FIELD = 'field';
    /** @var string */
    public const GROUP_BY_DIRECTION = 'direction';
    /** @var string */
    public const GROUP_BY_DIRECTION_ASC = 'asc';
    /** @var string */
    public const GROUP_BY_DIRECTION_DESC = 'desc';
    /** @var string */
    public const GROUP_BY_AGGREGATES = 'aggregates';
    /** @var string */
    public const GROUP_BY_AGGREGATE_FUNCTION = 'function';
    /** @var string */
    public const GROUP_BY_AGGREGATE_FIELD = 'field';
    /** @var string */
    public const GROUP_BY_ITEMS = 'items';
    /** @var string */
    public const GROUP_BY_VALUE = 'value';
    /** @var string */
    public const GROUP_BY_HAS_GROUPS = 'hasGroups';

    /** @var string */
    public const RESPONSE_STRUCTURE = 'responseStructure';

    /** @var string */
    public const ENTITY_NAME = 'entityName';

    /** @var string */
    public const NULL_VALUE = 'null';
    /** @var string */
    public const EMPTY_VALUE = '';
    /** @var string */
    public const TRUE_VALUE = 'true';
    /** @var string */
    public const FALSE_VALUE = 'false';
    /** @var string */
    public const ARRAY_VALUE = 'array';
    /** @var string */
    public const OBJECT_VALUE = 'object';
    /** @var string */
    public const UNDEFINED_VALUE = 'undefined';
}

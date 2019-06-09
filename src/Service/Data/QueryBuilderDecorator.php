<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class QueryBuilderDecorator
{
    /** @var string[] */
    private const BINARY_OPERATORS = [
        ParameterEnum::FILTER_OPERATOR_EQUAL,
        ParameterEnum::FILTER_OPERATOR_NOT_EQUAL,
        ParameterEnum::FILTER_OPERATOR_GREATER_THAN,
        ParameterEnum::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL,
        ParameterEnum::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL_OR_NULL,
        ParameterEnum::FILTER_OPERATOR_LOWER_THAN,
        ParameterEnum::FILTER_OPERATOR_LOWER_THAN_OR_EQUAL,
        ParameterEnum::FILTER_OPERATOR_BEGINS_WITH,
        ParameterEnum::FILTER_OPERATOR_CONTAINS,
        ParameterEnum::FILTER_OPERATOR_CONTAINS_NOT,
        ParameterEnum::FILTER_OPERATOR_ENDS_WITH,
        ParameterEnum::FILTER_OPERATOR_IS_EMPTY,
        ParameterEnum::FILTER_OPERATOR_IS_NOT_EMPTY,
        ParameterEnum::FILTER_OPERATOR_IN,
    ];
    /** @var string */
    public const SQL_WILDCARD = '%';
    /** @var string */
    private const PARAM_NAME_SEPARATOR = '_';
    /** @var string */
    public const DOCTRINE_ASSOCIATION_TYPE = 'type';
    /** @var string */
    public const DOCTRINE_TARGET_ENTITY = 'targetEntity';
    /** @var string */
    public const IDENTIFIER_FIELD_NAME = 'id';
    /** @var string */
    public const AGGREGATE_PREFIX = 'aggregate';
    /** @var string */
    public const AGGREGATE_FUNCTION_SEPARATOR = '_';

    /** @var RepositoryManager */
    private $repositoryManager;

    /**
     * QueryBuilderDecorator constructor.
     * @param RepositoryManager $repositoryManager
     */
    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * @param RA $filterData
     * @return string
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getFilteringLogic(RA $filterData): string
    {
        $logic = strtolower($filterData->getStringOrNull(ParameterEnum::FILTER_LOGIC))
            ?? ParameterEnum::FILTER_LOGIC_AND;
        if (true !== in_array($logic, ParameterEnum::AVAILABLE_FILTERING_LOGICS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_LOGIC,
                [$logic, implode(', ', ParameterEnum::AVAILABLE_FILTERING_LOGICS)]
            );
        }
        return $logic;
    }

    /**
     * @param RA $conditionData
     * @return string
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getFilteringField(RA $conditionData): string
    {
        if (true !== $conditionData->hasKey(ParameterEnum::FILTER_VALUE)) {
            throw new FilteringException(FilteringException::EXCEPTION_MISSING_FILTER_FIELD);
        }
        return $conditionData->getString(ParameterEnum::FILTER_FIELD);
    }

    /**
     * @param RA $filterData
     * @return string
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getFilteringOperator(RA $filterData): string
    {
        $operator = strtolower($filterData->getStringOrNull(ParameterEnum::FILTER_OPERATOR))
            ?? ParameterEnum::FILTER_OPERATOR_EQUAL;
        if (true !== in_array($operator, ParameterEnum::AVAILABLE_FILTERING_OPERATORS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR,
                [$operator, implode(', ', ParameterEnum::AVAILABLE_FILTERING_OPERATORS)]
            );
        }
        return $operator;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function containsWildcard(string $value): bool
    {
        return null !== (new Stringy($value))->getPositionOfSubstring(ParameterEnum::FILTER_VALUE_WILDCARD);
    }

    /**
     * @param string $operator
     * @return string
     */
    private function replaceWildcardOperator(string $operator): string
    {
        if ($operator === ParameterEnum::FILTER_OPERATOR_EQUAL) {
            return ParameterEnum::FILTER_OPERATOR_CONTAINS;
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_NOT_EQUAL) {
            return ParameterEnum::FILTER_OPERATOR_CONTAINS_NOT;
        }
        return $operator;
    }

    /**
     * @param Stringy $field
     * @return bool
     * @throws \Safe\Exceptions\StringsException
     */
    private function isManyToManyField(Stringy $field): bool
    {
        $associations = $this->repositoryManager->getCurrentMetadata()->associationMappings;
        $clonedField = (string)((clone $field)->replace(\Safe\sprintf('%s.', DataManager::ROOT_ALIAS), ''));
        return true === array_key_exists($field, $associations) &&
            $associations[$field][self::DOCTRINE_ASSOCIATION_TYPE] & ClassMetadataInfo::TO_MANY;
    }

    /**
     * @param Stringy $field
     * @return Stringy
     * @throws \Safe\Exceptions\PcreException
     */
    private function getFilteringPathForField(Stringy $field): Stringy
    {
        $field = $field->pregReplace('/^.*\.([A-Za-z0-9]+\.[A-Za-z0-9]+)$/', '$1');
        if (true === $this->isManyToManyField($field)) {
            $field = $field
                ->replace(\Safe\sprintf('%s.', DataManager::ROOT_ALIAS), '')
                ->concat(\Safe\sprintf('.%s', self::IDENTIFIER_FIELD_NAME));
        }
        return $field;
    }

    /**
     * @param Stringy $field
     * @return Stringy
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    private function resolveFilteringConditionFieldName(Stringy $field): Stringy
    {
        if (true === $this->isEmbed($field)) {
            return \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR, $field);
        }
        
        $field = $this->getFilteringPathForField($field);
        return $field;
    }

    /**
     * @param Stringy $field
     * @param string $operator
     * @param string $parameterName
     * @return string
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    private function createCondition(Stringy $field, string $operator, string $parameterName): string
    {
        $field = (string)($this->resolveFilteringConditionFieldName($field));

        $expression = new Expr();
        if ($operator === ParameterEnum::FILTER_OPERATOR_EQUAL) {
            return (string)($expression->eq($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_NOT_EQUAL) {
            return (string)($expression->neq($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_GREATER_THAN) {
            return (string)($expression->gt($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL) {
            return (string)($expression->gte($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_GREATER_THAN_OR_EQUAL_OR_NULL) {
            return (string)($expression->orX(
                $expression->gte($field, $parameterName),
                $expression->isNull($field)
            ));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_LOWER_THAN) {
            return (string)($expression->lt($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_LOWER_THAN_OR_EQUAL) {
            return (string)($expression->lte($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_BEGINS_WITH) {
            return (string)($expression->like($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_CONTAINS) {
            return (string)($expression->like($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_CONTAINS_NOT) {
            return (string)($expression->notLike($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_ENDS_WITH) {
            return (string)($expression->like($field, $parameterName));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_IS_NULL) {
            return (string)($expression->isNull($field));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_IS_NOT_NULL) {
            return (string)($expression->isNotNull($field));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_IS_EMPTY) {
            return (string)($expression->orX(
                $expression->isNull($field),
                $expression->eq($field, $parameterName)
            ));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_IS_NOT_EMPTY) {
            return (string)($expression->andX(
                $expression->isNotNull($field),
                $expression->neq($field, $parameterName)
            ));
        }
        if ($operator === ParameterEnum::FILTER_OPERATOR_IN) {
            return (string)($expression->in($field, $parameterName));
        }
        throw new FilteringException(
            FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR,
            [$operator, implode(', ', ParameterEnum::AVAILABLE_FILTERING_OPERATORS)]
        );
    }

    /**
     * @param Stringy $field
     * @return bool
     */
    private function isEmbed(Stringy $field): bool
    {
        if (null !== $field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR)) {
            $embeddedEntities = $this->repositoryManager->getCurrentMetadata()->embeddedClasses;
            return array_key_exists($field->explode(ParameterEnum::FILTER_FIELD_SEPARATOR)[0], $embeddedEntities);
        }
        return false;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Stringy $field
     * @return QueryBuilderDecorator
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    private function joinRequiredFilteringRelations(QueryBuilder $queryBuilder, Stringy $field): self
    {
        if (true === $field->pregMatch('/^[a-z\.]+$/i')) {
            $currentPrefix = clone $field;
            $firstSeparatorPosition = $field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR);
            if (null !== $firstSeparatorPosition) {
                $currentPrefix = $currentPrefix->substring(0, $firstSeparatorPosition);
            }

            if ($currentPrefix !== DataManager::ROOT_ALIAS) {
                $previousPrefix = DataManager::ROOT_ALIAS;
                $currentField = (clone $field)->substring($currentPrefix->length() + 1);
                while (true !== $currentPrefix->sameAs($previousPrefix)) {
                    if (true !== in_array((string)$currentPrefix, $queryBuilder->getAllAliases(), true)) {
                        $queryBuilder->leftJoin(
                            \Safe\sprintf('%s.%s', $previousPrefix, $currentPrefix),
                            (string)$currentPrefix
                        );
                    }
                    $previousPrefix = clone $currentPrefix;
                    $nextSeparatorPosition = $currentField
                        ->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR);
                    if (null !== $nextSeparatorPosition) {
                        $currentPrefix = (clone $currentField)->substring(0, $nextSeparatorPosition);
                        $currentField = $currentField->substring($nextSeparatorPosition + 1);
                    }
                }
                return $this;
            }

            $currentField = (clone $field)
                ->replace(\Safe\sprintf('%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR), '');
            if (true === $this->isManyToManyField($currentField) &&
                true !== in_array((string)$currentField, $queryBuilder->getAllAliases(), true)
            ) {
                $queryBuilder->leftJoin(\Safe\sprintf('%s.%s', DataManager::ROOT_ALIAS, $currentField), $currentField);
            }
        }
        return $this;
    }

    /**
     * @param string $operator
     * @return bool
     */
    private function isBinaryOperator(string $operator): bool
    {
        return in_array($operator, self::BINARY_OPERATORS, true);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA $conditionData
     * @param int $conditionKey
     * @param int $filteringKey
     * @return string
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getFilteringCondition(
        QueryBuilder $queryBuilder,
        RA $conditionData,
        int $conditionKey,
        int $filteringKey
    ): string {
        $field = new Stringy($this->getFilteringField($conditionData));
        $operator = $this->getFilteringOperator($conditionData);
        $value = $conditionData->get(ParameterEnum::FILTER_VALUE);
        
        if (true === is_string($value) && $this->containsWildcard($value)) {
            $replacementOperator = $this->replaceWildcardOperator($operator);
            if ($replacementOperator !== $operator) {
                $operator = $replacementOperator;
                $value = (string)((new Stringy($value))
                    ->replace(ParameterEnum::FILTER_VALUE_WILDCARD, self::SQL_WILDCARD));
            }
        }

        $parameterName = \Safe\sprintf(
            '%s_%s_%d_%d',
            (new Stringy())->replace(ParameterEnum::FILTER_FIELD_SEPARATOR, self::PARAM_NAME_SEPARATOR),
            $operator,
            $filteringKey,
            $conditionKey
        );

        $condition = $this->createCondition($field, $operator, \Safe\sprintf(':%s', $parameterName));

        if (true !== $this->isEmbed($field)) {
            $this->joinRequiredFilteringRelations($queryBuilder, $field);
        }

        if (true === $this->isBinaryOperator($operator)) {
            $queryBuilder->setParameter($parameterName, $value);
        }

        return $condition;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA $conditions
     * @param int $filteringKey
     * @return RA
     */
    private function prepareFilteringConditions(QueryBuilder $queryBuilder, RA $conditions, int $filteringKey): RA
    {
        return $conditions->map(function (RA $conditionData, int $key) use ($queryBuilder, $filteringKey): string {
            if (true === $conditionData->hasKey(ParameterEnum::FILTER_CONDITIONS)) {
                $logic = $this->getFilteringLogic($conditionData);
                $conditions = $this->prepareFilteringConditions(
                    $queryBuilder,
                    $conditionData->getRA(ParameterEnum::FILTER_CONDITIONS),
                    $filteringKey
                );
                return \Safe\sprintf('(%s)', $conditions->join(\Safe\sprintf(' %s ', $logic)));
            }
            return $this->getFilteringCondition($queryBuilder, $conditionData, $key, $filteringKey);
        });
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA $filters
     * @return QueryBuilderDecorator
     */
    public function applyFiltering(QueryBuilder $queryBuilder, RA $filters): self
    {
        $filters->walk(function (RA $filterData, int $key) use ($queryBuilder): void {
            if (true === $filterData->hasKey(ParameterEnum::FILTER_CONDITIONS)) {
                $logic = $this->getFilteringLogic($filterData);
                $conditions = $this->prepareFilteringConditions(
                    $queryBuilder,
                    $filterData->getRA(ParameterEnum::FILTER_CONDITIONS),
                    $key
                );
                $logic === ParameterEnum::FILTER_LOGIC_AND
                    ? $queryBuilder->andWhere(...$conditions->toArray())
                    : $queryBuilder->orWhere(...$conditions->toArray());
            }
        });
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA $orderings
     * @return QueryBuilderDecorator
     */
    public function applyOrdering(QueryBuilder $queryBuilder, RA $orderings): self
    {
        $orderings->walk(function (RA $orderData) use ($queryBuilder): void {
            $field = new Stringy($orderData->getString(ParameterEnum::ORDER_BY_FIELD));
            $direction = $orderData->getString(ParameterEnum::ORDER_BY_DIRECTION);

            if (true !== in_array($direction, ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS, true)) {
                throw new FilteringException(
                    FilteringException::EXCEPTION_INVALID_ORDERING_DIRECTION,
                    [$direction, implode(', ', ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS)]
                );
            }

            if (true === $this->isEmbed($field)) {
                $queryBuilder->addOrderBy($field, $direction);
                return;
            }

            $this->joinRequiredFilteringRelations($queryBuilder, $field);
            $queryBuilder->addOrderBy($this->getFilteringPathForField($field), $direction);
        });
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $offset
     * @param int $limit
     * @return QueryBuilderDecorator
     */
    public function applyPagination(QueryBuilder $queryBuilder, int $offset, int $limit): self
    {
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $field
     * @return QueryBuilderDecorator
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    public function applyGroupping(QueryBuilder $queryBuilder, string $field): self
    {
        if (true === $this->isEmbed($field)) {
            $queryBuilder->groupBy(\Safe\sprintf('%s.%s', DataManager::ROOT_ALIAS, $field));
            return $this;
        }
        $this->joinRequiredFilteringRelations($queryBuilder, new Stringy($field));
        $queryBuilder->groupBy($this->getFilteringPathForField($field));
        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA $aggregates
     * @return QueryBuilderDecorator
     */
    public function applyAggregates(QueryBuilder $queryBuilder, RA $aggregates): self
    {
        $aggregates->walk(function (RA $aggregateData) use ($queryBuilder): void {
            $function = $aggregateData->getString(ParameterEnum::GROUP_BY_AGGREGATE_FUNCTION);
            $field = $aggregateData->getString(ParameterEnum::GROUP_BY_AGGREGATE_FIELD);
            $queryBuilder->addSelect(\Safe\sprintf(
                '%s(DISTINCT(%s.%s)) AS %s%s%s%s%s',
                $function,
                DataManager::ROOT_ALIAS,
                $field,
                self::AGGREGATE_PREFIX,
                self::AGGREGATE_FUNCTION_SEPARATOR,
                $function,
                self::AGGREGATE_FUNCTION_SEPARATOR,
                $field
            ));
        });
        return $this;
    }
}

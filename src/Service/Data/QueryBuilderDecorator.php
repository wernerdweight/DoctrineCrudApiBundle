<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class QueryBuilderDecorator
{
    /** @var string[] */
    private const AVAILABLE_FILTERING_LOGICS = [
        ParameterEnum::FILTER_LOGIC_AND,
        ParameterEnum::FILTER_LOGIC_OR,
    ];
    /** @var string[] */
    private const AVAILABLE_FILTERING_OPERATORS = [
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
        ParameterEnum::FILTER_OPERATOR_IS_NULL,
        ParameterEnum::FILTER_OPERATOR_IS_NOT_NULL,
        ParameterEnum::FILTER_OPERATOR_IS_EMPTY,
        ParameterEnum::FILTER_OPERATOR_IS_NOT_EMPTY,
        ParameterEnum::FILTER_OPERATOR_IN,
    ];
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
    private const SQL_WILDCARD = '%';
    /** @var string */
    private const PARAM_NAME_SEPARATOR = '_';
    /** @var string */
    private const DOCTRINE_ASSOCIATION_TYPE = 'type';
    /** @var string */
    private const IDENTIFIER_FIELD_NAME = 'id';

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
        if (true !== in_array($logic, self::AVAILABLE_FILTERING_LOGICS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_LOGIC,
                [$logic, implode(', ', self::AVAILABLE_FILTERING_LOGICS)]
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
        if (true !== in_array($operator, self::AVAILABLE_FILTERING_OPERATORS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR,
                [$operator, implode(', ', self::AVAILABLE_FILTERING_OPERATORS)]
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

    private function createCondition(Stringy $field, string $operator, string $parameterName): string
    {
        $field = $this->resolveFilteringConditionFieldName($field);

        // TODO:
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

    private function joinRequiredFilteringRelations(QueryBuilder $queryBuilder, Stringy $field): self
    {
        // TODO:
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
        return $conditions->map(function (RA $conditionData, int $key) use ($queryBuilder, $filteringKey): void {
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

    public function applyOrdering(QueryBuilder $queryBuilder, RA $filters): self
    {
        // TODO:
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
}

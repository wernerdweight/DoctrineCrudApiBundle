<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Request;

use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\DataManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\MappingResolver;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\RepositoryManager;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ParameterValidator
{
    /** @var RepositoryManager */
    private $repositoryManager;

    /** @var MappingResolver */
    private $mappingResolver;

    /**
     * ParameterValidator constructor.
     *
     * @param RepositoryManager $repositoryManager
     * @param MappingResolver   $mappingResolver
     */
    public function __construct(RepositoryManager $repositoryManager, MappingResolver $mappingResolver)
    {
        $this->repositoryManager = $repositoryManager;
        $this->mappingResolver = $mappingResolver;
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    private function validateFilteringOperator(string $operator): string
    {
        if (true !== in_array($operator, ParameterEnum::AVAILABLE_FILTERING_OPERATORS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR,
                [$operator, implode(', ', ParameterEnum::AVAILABLE_FILTERING_OPERATORS)]
            );
        }
        return $operator;
    }

    /**
     * @param string $direction
     *
     * @return string
     */
    private function validateDirection(string $direction): string
    {
        if (true !== in_array($direction, ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_ORDERING_DIRECTION,
                [$direction, implode(', ', ParameterEnum::AVAILABLE_ORDERING_DIRECTIONS)]
            );
        }
        return $direction;
    }

    /**
     * @param Stringy $field
     *
     * @return bool
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function fieldContainsRootAlias(Stringy $field): bool
    {
        $rootAlias = \Safe\sprintf('%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR);
        return 0 === $field->getPositionOfSubstring($rootAlias) ||
            0 === $field->getPositionOfSubstring($this->repositoryManager->getCurrentEntityName());
    }

    /**
     * @param Stringy $field
     * @param string  $operator
     * @param mixed   $value
     *
     * @return mixed
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAExceptio
     */
    private function validateFilteringValue(Stringy $field, string $operator, $value)
    {
        $configuration = $this->repositoryManager->getCurrentMappings();
        $configuration = true === $configuration->hasKey((string)$field)
            ? $configuration->getRAOrNull((string)$field)
            : $this->repositoryManager->getMappingForField($field);

        if (null !== $configuration) {
            $value = $this->mappingResolver->resolveValue($configuration, $value);
        }

        if (ParameterEnum::FILTER_OPERATOR_BEGINS_WITH === $operator) {
            return \Safe\sprintf('%s%s', $value, QueryBuilderDecorator::SQL_WILDCARD);
        }
        $containsOperators = [ParameterEnum::FILTER_OPERATOR_CONTAINS, ParameterEnum::FILTER_OPERATOR_CONTAINS_NOT];
        if (true === in_array($operator, $containsOperators, true)) {
            return \Safe\sprintf(
                '%s%s%s',
                QueryBuilderDecorator::SQL_WILDCARD,
                $value,
                QueryBuilderDecorator::SQL_WILDCARD
            );
        }
        if (ParameterEnum::FILTER_OPERATOR_ENDS_WITH === $operator) {
            return \Safe\sprintf('%s%s', QueryBuilderDecorator::SQL_WILDCARD, $value);
        }
        $emptyOperators = [ParameterEnum::FILTER_OPERATOR_IS_EMPTY, ParameterEnum::FILTER_OPERATOR_IS_NOT_EMPTY];
        if (true === in_array($operator, $emptyOperators, true)) {
            return ParameterEnum::EMPTY_VALUE;
        }

        return $value;
    }

    /**
     * @param RA $conditions
     *
     * @return RA
     */
    private function validateFilteringConditions(RA $conditions): RA
    {
        return $conditions->map(function (RA $condition): RA {
            if (true === $condition->hasKey(ParameterEnum::FILTER_CONDITIONS)) {
                return new RA([
                    ParameterEnum::FILTER_LOGIC => $this->validateFilteringLogic(
                        $condition->getString(ParameterEnum::FILTER_LOGIC ?? ParameterEnum::FILTER_LOGIC_AND)
                    ),
                    ParameterEnum::FILTER_CONDITIONS => $this->validateFilteringConditions(
                        $condition->getRA(ParameterEnum::FILTER_CONDITIONS)
                    ),
                ]);
            }
            if (true !== $condition->hasKey(ParameterEnum::FILTER_FIELD)) {
                throw new FilteringException(FilteringException::EXCEPTION_MISSING_FILTER_FIELD);
            }
            $field = new Stringy($condition->getString(ParameterEnum::FILTER_FIELD));
            $operator = $this->validateFilteringOperator($condition->getString(ParameterEnum::FILTER_OPERATOR));
            $value = $condition->get(ParameterEnum::FILTER_VALUE);

            if (true === $this->fieldContainsRootAlias($field)) {
                $field = $field->substring($field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR) + 1);
            }

            return new RA([
                ParameterEnum::FILTER_FIELD => null === $field->getPositionOfSubstring(
                    ParameterEnum::FILTER_FIELD_SEPARATOR
                )
                    ? new Stringy(
                        \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR, $field)
                    )
                    : clone $field,
                ParameterEnum::FILTER_OPERATOR => $operator,
                ParameterEnum::FILTER_VALUE => $this->validateFilteringValue($field, $operator, $value),
            ]);
        });
    }

    /**
     * @param string $logic
     *
     * @return string
     */
    private function validateFilteringLogic(string $logic): string
    {
        if (true !== in_array($logic, ParameterEnum::AVAILABLE_FILTERING_LOGICS, true)) {
            throw new FilteringException(
                FilteringException::EXCEPTION_INVALID_FILTER_LOGIC,
                [$logic, implode(', ', ParameterEnum::AVAILABLE_FILTERING_LOGICS)]
            );
        }
        return $logic;
    }

    /**
     * @param array|null $filter
     *
     * @return RA
     */
    public function validateFilter(?array $filter): RA
    {
        if (null === $filter || true !== isset($filter[ParameterEnum::FILTER_CONDITIONS])) {
            return new RA();
        }

        $conditions = $this->validateFilteringConditions(
            new RA($filter[ParameterEnum::FILTER_CONDITIONS], RA::RECURSIVE)
        );

        if (0 === $conditions->length()) {
            return new RA();
        }

        return new RA([
            ParameterEnum::FILTER_LOGIC => $this->validateFilteringLogic(
                $filter[ParameterEnum::FILTER_LOGIC] ?? ParameterEnum::FILTER_LOGIC_AND
            ),
            ParameterEnum::FILTER_CONDITIONS => $conditions,
        ]);
    }

    /**
     * @param array|null $orderBy
     *
     * @return RA
     */
    public function validateOrderBy(?array $orderBy): RA
    {
        if (null === $orderBy) {
            return new RA();
        }

        $orderBy = new RA($orderBy, RA::RECURSIVE);
        return $orderBy->map(function (RA $entry): RA {
            $direction = $this->validateDirection(
                (
                    true === $entry->hasKey(ParameterEnum::ORDER_BY_DIRECTION)
                        ? $entry->getStringOrNull(ParameterEnum::ORDER_BY_DIRECTION)
                        : null
                ) ?? ParameterEnum::ORDER_BY_DIRECTION_ASC
            );
            $field = new Stringy($entry->getString(ParameterEnum::ORDER_BY_FIELD));
            if (null === $field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR)) {
                $field = new Stringy(
                    \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR, $field)
                );
            }
            return new RA(compact('field', 'direction'));
        });
    }

    /**
     * @param array|null $groupBy
     *
     * @return RA|null
     */
    public function validateGroupBy(?array $groupBy): ?RA
    {
        if (null === $groupBy) {
            return null;
        }

        $groupBy = new RA($groupBy, RA::RECURSIVE);
        return $groupBy->map(function (RA $entry): RA {
            $direction = $this->validateDirection(
                $entry->getStringOrNull(ParameterEnum::GROUP_BY_DIRECTION) ?? ParameterEnum::GROUP_BY_DIRECTION_ASC
            );
            $field = new Stringy($entry->getString(ParameterEnum::GROUP_BY_FIELD));
            if (null === $field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR)) {
                $field = new Stringy(
                    \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR, $field)
                );
            }
            $aggregates = (
                $entry->hasKey(ParameterEnum::GROUP_BY_AGGREGATES)
                    ? $entry->getRAOrNull(ParameterEnum::GROUP_BY_AGGREGATES)
                    : null
                ) ?? new RA();
            return new RA(compact('field', 'direction', 'aggregates'));
        });
    }

    /**
     * @param array|null $responseStructure
     * @param Stringy    $entityName
     *
     * @return RA|null
     */
    public function validateResponseStructure(?array $responseStructure, Stringy $entityName): ?RA
    {
        if (null === $responseStructure) {
            return null;
        }
        return new RA([(string)$entityName => $responseStructure], RA::RECURSIVE);
    }
}
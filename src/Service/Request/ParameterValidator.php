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
     * @param RepositoryManager $repositoryManager
     * @param MappingResolver $mappingResolver
     */
    public function __construct(RepositoryManager $repositoryManager, MappingResolver $mappingResolver)
    {
        $this->repositoryManager = $repositoryManager;
        $this->mappingResolver = $mappingResolver;
    }

    /**
     * @param string $operator
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
     * @param Stringy $field
     * @return bool
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function fieldContainsRootAlias(Stringy $field): bool
    {
        $rootAlias = \Safe\sprintf('%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR);
        return $field->getPositionOfSubstring($rootAlias) === 0 ||
            $field->getPositionOfSubstring($this->repositoryManager->getCurrentEntityName()) === 0;
    }

    /**
     * @param Stringy $field
     * @param string $operator
     * @param mixed $value
     * @return mixed
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAExceptio
     */
    private function validateFilteringValue(Stringy $field, string $operator, $value)
    {
        $configuration = $this->repositoryManager->getCurrentMappings()->getRAOrNull((string)$field)
            ?? $this->repositoryManager->getMappingForField($field);
        
        if (null !== $configuration) {
            $value = $this->mappingResolver->resolveValue($configuration, $value);
        }

        if ($operator === ParameterEnum::FILTER_OPERATOR_BEGINS_WITH) {
            return \Safe\sprintf('%s%s', QueryBuilderDecorator::SQL_WILDCARD, $value);
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
        if ($operator === ParameterEnum::FILTER_OPERATOR_ENDS_WITH) {
            return \Safe\sprintf('%s%s', $value, QueryBuilderDecorator::SQL_WILDCARD);
        }
        $emptyOperators = [ParameterEnum::FILTER_OPERATOR_IS_EMPTY, ParameterEnum::FILTER_OPERATOR_IS_NOT_EMPTY];
        if (true === in_array($operator, $emptyOperators, true)) {
            return '';
        }

        return $value;
    }

    /**
     * @param RA $conditions
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
                ParameterEnum::FILTER_FIELD => null === $field->getPositionOfSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR)
                    ? \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FILTER_FIELD_SEPARATOR, $field)
                    : $field,
                ParameterEnum::FILTER_OPERATOR => $operator,
                ParameterEnum::FILTER_VALUE => $this->validateFilteringValue($field, $operator, $value),
            ]);
        });
    }

    /**
     * @param string $logic
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
     * @return RA
     */
    public function validateFilter(?array $filter): RA
    {
        if (null === $filter || true !== isset($filter[ParameterEnum::FILTER_CONDITIONS])) {
            return new RA();
        }

        $conditions = $this->validateFilteringConditions(
            new RA($filter[ParameterEnum::FILTER_CONDITIONS]),
            RA::RECURSIVE
        );

        if ($conditions->length() === 0) {
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
     * @return RA
     */
    public function validateOrderBy(?array $orderBy): RA
    {
        if (null === $orderBy) {
            return new RA();
        }

        return ;
    }

    /**
     * @param array|null $groupBy
     * @return RA
     */
    public function validateGroupBy(?array $groupBy): RA
    {
        if (null === $groupBy) {
            return new RA();
        }

        return ;
    }
}

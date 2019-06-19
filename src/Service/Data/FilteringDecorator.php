<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\QueryBuilder;
use WernerDweight\DoctrineCrudApiBundle\Service\ConditionGenerator\ConditionGeneratorFactory;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class FilteringDecorator
{
    /** @var string */
    public const SQL_WILDCARD = '%';
    /** @var string */
    private const PARAM_NAME_SEPARATOR = '_';

    /** @var ConditionGeneratorFactory */
    private $conditionGeneratorFactory;

    /** @var FilteringHelper */
    private $filteringHelper;

    /** @var RelationJoiner */
    private $relationJoiner;

    /**
     * FilteringDecorator constructor.
     *
     * @param ConditionGeneratorFactory $conditionGeneratorFactory
     * @param FilteringHelper           $filteringHelper
     * @param RelationJoiner            $relationJoiner
     */
    public function __construct(
        ConditionGeneratorFactory $conditionGeneratorFactory,
        FilteringHelper $filteringHelper,
        RelationJoiner $relationJoiner
    ) {
        $this->conditionGeneratorFactory = $conditionGeneratorFactory;
        $this->filteringHelper = $filteringHelper;
        $this->relationJoiner = $relationJoiner;
    }

    /**
     * @param Stringy $field
     * @param string  $operator
     * @param string  $parameterName
     *
     * @return string
     *
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    private function createCondition(Stringy $field, string $operator, string $parameterName): string
    {
        $field = (string)($this->filteringHelper->resolveFilteringConditionFieldName($field));
        return $this->conditionGeneratorFactory->get($operator)->generate($field, $parameterName);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA           $conditionData
     * @param int          $conditionKey
     * @param int          $filteringKey
     *
     * @return string
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getFilteringCondition(
        QueryBuilder $queryBuilder,
        RA $conditionData,
        int $conditionKey,
        int $filteringKey
    ): string {
        $field = $this->filteringHelper->getFilteringField($conditionData);
        $operator = $this->filteringHelper->getFilteringOperator($conditionData);
        $value = $conditionData->get(ParameterEnum::FILTER_VALUE);

        if (true === is_string($value) && $this->filteringHelper->containsWildcard($value)) {
            $replacementOperator = $this->filteringHelper->replaceWildcardOperator($operator);
            if ($replacementOperator !== $operator) {
                $operator = $replacementOperator;
                $value = (string)((new Stringy($value))
                    ->replace(ParameterEnum::FILTER_VALUE_WILDCARD, self::SQL_WILDCARD));
            }
        }

        $parameterName = \Safe\sprintf(
            '%s_%s_%d_%d',
            (clone $field)->replace(ParameterEnum::FIELD_SEPARATOR, self::PARAM_NAME_SEPARATOR),
            $operator,
            $filteringKey,
            $conditionKey
        );

        $condition = $this->createCondition($field, $operator, \Safe\sprintf(':%s', $parameterName));

        if (true !== $this->filteringHelper->isEmbed($field)) {
            $this->relationJoiner->joinRequiredRelations($queryBuilder, $field);
        }

        if (true === $this->filteringHelper->isBinaryOperator($operator)) {
            $queryBuilder->setParameter($parameterName, $value);
        }

        return $condition;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param RA           $conditions
     * @param int          $filteringKey
     *
     * @return RA
     */
    public function prepareFilteringConditions(QueryBuilder $queryBuilder, RA $conditions, int $filteringKey): RA
    {
        return $conditions->map(function (RA $conditionData, int $key) use ($queryBuilder, $filteringKey): string {
            if (true === $conditionData->hasKey(ParameterEnum::FILTER_CONDITIONS)) {
                $logic = $this->filteringHelper->getFilteringLogic($conditionData);
                $conditions = $this->prepareFilteringConditions(
                    $queryBuilder,
                    $conditionData->getRA(ParameterEnum::FILTER_CONDITIONS),
                    $key
                );
                return \Safe\sprintf('(%s)', $conditions->join(\Safe\sprintf(' %s ', $logic)));
            }
            return $this->getFilteringCondition($queryBuilder, $conditionData, $key, $filteringKey);
        }, $conditions->keys());
    }
}

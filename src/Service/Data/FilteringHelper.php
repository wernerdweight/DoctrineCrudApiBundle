<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Data;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Exception\FilteringException;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class FilteringHelper
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
    public const DOCTRINE_ASSOCIATION_TYPE = 'type';
    /** @var string */
    public const IDENTIFIER_FIELD_NAME = 'id';

    /** @var RepositoryManager */
    private $repositoryManager;

    /**
     * FilteringHelper constructor.
     */
    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFilteringLogic(RA $filterData): string
    {
        $logic = mb_strtolower(
            $filterData->getStringOrNull(ParameterEnum::FILTER_LOGIC) ?? ParameterEnum::FILTER_LOGIC_AND
        );
        if (true !== in_array($logic, ParameterEnum::AVAILABLE_FILTERING_LOGICS, true)) {
            throw new FilteringException(FilteringException::EXCEPTION_INVALID_FILTER_LOGIC, [
                $logic,
                implode(', ', ParameterEnum::AVAILABLE_FILTERING_LOGICS),
            ]);
        }
        return $logic;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFilteringField(RA $conditionData): Stringy
    {
        if (true !== $conditionData->hasKey(ParameterEnum::FILTER_FIELD)) {
            throw new FilteringException(FilteringException::EXCEPTION_MISSING_FILTER_FIELD);
        }
        /** @var Stringy $field */
        $field = $conditionData->get(ParameterEnum::FILTER_FIELD);
        return $field;
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFilteringOperator(RA $filterData): string
    {
        $operator = mb_strtolower(
            $filterData->getStringOrNull(ParameterEnum::FILTER_OPERATOR) ?? ParameterEnum::FILTER_OPERATOR_EQUAL
        );
        if (true !== in_array($operator, ParameterEnum::AVAILABLE_FILTERING_OPERATORS, true)) {
            throw new FilteringException(FilteringException::EXCEPTION_INVALID_FILTER_OPERATOR, [
                $operator,
                implode(', ', ParameterEnum::AVAILABLE_FILTERING_OPERATORS),
            ]);
        }
        return $operator;
    }

    public function containsWildcard(string $value): bool
    {
        return null !== (new Stringy($value))->getPositionOfSubstring(ParameterEnum::FILTER_VALUE_WILDCARD);
    }

    public function replaceWildcardOperator(string $operator): string
    {
        if (ParameterEnum::FILTER_OPERATOR_EQUAL === $operator) {
            return ParameterEnum::FILTER_OPERATOR_CONTAINS;
        }
        if (ParameterEnum::FILTER_OPERATOR_NOT_EQUAL === $operator) {
            return ParameterEnum::FILTER_OPERATOR_CONTAINS_NOT;
        }
        return $operator;
    }

    /**
     * @throws \Safe\Exceptions\StringsException
     */
    public function isManyToManyField(Stringy $field): bool
    {
        $associations = $this->repositoryManager->getCurrentMetadata()->associationMappings;
        $clonedField = (string)((clone $field)->replace(\Safe\sprintf('%s.', DataManager::ROOT_ALIAS), ''));
        return true === array_key_exists($clonedField, $associations) &&
            $associations[$clonedField][self::DOCTRINE_ASSOCIATION_TYPE] & ClassMetadataInfo::TO_MANY;
    }

    /**
     * @throws \Safe\Exceptions\PcreException
     */
    public function getFilteringPathForField(Stringy $field): Stringy
    {
        $field = (clone $field)->pregReplace('/^.*\.([A-Za-z0-9]+\.[A-Za-z0-9]+)$/', '$1');
        if (true === $this->isManyToManyField($field)) {
            $field = $field
                ->replace(\Safe\sprintf('%s.', DataManager::ROOT_ALIAS), '')
                ->concat(\Safe\sprintf('.%s', self::IDENTIFIER_FIELD_NAME));
        }
        return $field;
    }

    /**
     * @throws \Safe\Exceptions\PcreException
     * @throws \Safe\Exceptions\StringsException
     */
    public function resolveFilteringConditionFieldName(Stringy $field): Stringy
    {
        if (true === $this->isEmbed($field)) {
            return new Stringy(
                \Safe\sprintf('%s%s%s', DataManager::ROOT_ALIAS, ParameterEnum::FIELD_SEPARATOR, $field)
            );
        }

        $field = $this->getFilteringPathForField($field);
        return $field;
    }

    public function isEmbed(Stringy $field): bool
    {
        if (null !== $field->getPositionOfSubstring(ParameterEnum::FIELD_SEPARATOR)) {
            $embeddedEntities = $this->repositoryManager->getCurrentMetadata()->embeddedClasses;
            return array_key_exists($field->explode(ParameterEnum::FIELD_SEPARATOR)[0], $embeddedEntities);
        }
        return false;
    }

    public function isBinaryOperator(string $operator): bool
    {
        return in_array($operator, self::BINARY_OPERATORS, true);
    }
}

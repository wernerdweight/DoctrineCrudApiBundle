<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use Doctrine\Common\Collections\Collection;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterResolver;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class Formatter
{
    /** @var ParameterResolver */
    private $parameterResolver;

    /** @var ConfigurationManager */
    private $configurationManager;

    /**
     * Formatter constructor.
     * @param ParameterResolver $parameterResolver
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(ParameterResolver $parameterResolver, ConfigurationManager $configurationManager)
    {
        $this->parameterResolver = $parameterResolver;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param mixed $value
     * @return string
     * @throws \Safe\Exceptions\PcreException
     */
    private function printValue($value): string
    {
        if ($value === null) {
            return ParameterEnum::NULL_VALUE;
        }
        if ($value === true) {
            return ParameterEnum::TRUE_VALUE;
        }
        if ($value === false) {
            return ParameterEnum::FALSE_VALUE;
        }
        if (true === is_numeric($value)) {
            return (string)$value;
        }
        if (true === is_array($value)) {
            return ParameterEnum::ARRAY_VALUE;
        }
        if (true === is_object($value)) {
            if ($value instanceof \DateTime) {
                $formatedDateTime = new Stringy($value->format('c'));
                return (string)($formatedDateTime->pregReplace('/:([\d]{2})$/', '$1'));
            }
            if (true === method_exists($value, '__toString')) {
                return (string)$value;
            }
            return ParameterEnum::OBJECT_VALUE;
        }
        if (true === is_string($value)) {
            return $value;
        }
        return ParameterEnum::UNDEFINED_VALUE;
    }

    /**
     * @param Stringy $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param RA|null $responseStructure
     * @return bool
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function isAllowedForOutput(
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        ?RA $responseStructure = null
    ): bool {
        if (null === $responseStructure) {
            $responseStructure = $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE)
                ?? $configuration->getDefaultListableFields()->fillKeys(ParameterEnum::TRUE_VALUE);
        }

        $lastDotPosition = $field->getPositionOfLastSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR);
        if (null !== $lastDotPosition) {
            $key = (clone $field)->substring($lastDotPosition + 1);
            return true === $responseStructure->hasKey((string)$key);
        }

        if (true === $responseStructure->hasKey((string)$field)) {
            $value = $responseStructure->get((string)$field);
            return ($value === ParameterEnum::TRUE_VALUE || $value instanceof RA);
        }
        return false;
    }

    /**
     * @param ApiEntityInterface $item
     * @param Stringy $field
     * @param array $args
     * @return mixed
     */
    private function getEntityPropertyValue(ApiEntityInterface $item, Stringy $field, array $args = [])
    {
        $propertyName = (clone $field)->uppercaseFirst();
        if (true === method_exists($item, 'get' . $propertyName)) {
            return $item->{'get' . $propertyName}(...$args);
        }
        if (true === method_exists($item, 'is' . $propertyName)) {
            return $item->{'is' . $propertyName}(...$args);
        }
        if (true === method_exists($item, $field)) {
            return $item->{$field}(...$args);
        }
        if (true === property_exists($item, $field)) {
            return $item->$field;
        }
        throw new FormatterException(
            FormatterException::EXCEPTION_NO_PROPERTY_GETTER,
            [$field, get_class($item)]
        );
    }

    private function getRelatedEntityValue(ApiEntityInterface $item, Stringy $field, DoctrineCrudApiMetadata $configuration, string $prefix)
    {

    }

    /**
     * @param ApiEntityInterface $item
     * @param Stringy $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param string $prefix
     * @return RA
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getRelatedCollectionValue(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        string $prefix
    ): RA {
        $fieldMetadata = $configuration->getFieldMetadata((string)$field);
        $payload = null !== $fieldMetadata && $fieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)
            ? $fieldMetadata->getRAOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)
            : [];
        /** @var Collection $fieldValue */
        $fieldValue = $this->getEntityPropertyValue($item, $field, $payload);
        return (new RA($fieldValue->getValues()))->map(function (ApiEntityInterface $entry) use ($prefix, $field) {
            return $this->formatOne($entry, \Safe\sprintf('%s%s.', $prefix, (string)$field));
        });
    }

    /**
     * @param ApiEntityInterface $item
     * @param Stringy $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param string $prefix
     * @return mixed
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getEntityPropertyValueBasedOnMetadata(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        string $prefix
    ) {
        $type = $configuration->getFieldType((string)$field);
        if (null === $type) {
            return $this->getEntityPropertyValue($item, $field);
        }
        if ($type === DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY) {
            return $this->getRelatedEntityValue($item, $field, $configuration, $prefix);
        }
        if ($type === DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION) {
            return $this->getRelatedCollectionValue($item, $field, $configuration, $prefix);
        }
        throw new FormatterException(FormatterException::EXCEPTION_INVALID_METADATA_TYPE, [$type]);
    }

    /**
     * @param ApiEntityInterface $item
     * @param string $prefix
     * @return RA
     */
    public function formatOne(ApiEntityInterface $item, string $prefix = ParameterEnum::EMPTY_VALUE): RA
    {
        $configuration = $this->configurationManager->getConfigurationForEntity($item);
        $result = new RA();
        $configuration->getListableFields()->walk(function (string $field) use ($item, $prefix, $result, $configuration): void {
            if (true !== $this->isAllowedForOutput(new Stringy(\Safe\sprintf('%s%s', $prefix, $field)), $configuration)) {
                return;
            }
            $metadata = $configuration->getFieldMetadata($field);
            if (null === $metadata) {
                $result->set($field, $this->getEntityPropertyValue($item, new Stringy($field)));
                return;
            }
            $result->set($field, $this->getEntityPropertyValueBasedOnMetadata($item, new Stringy($field), $configuration, $prefix));
        });
        return $result;
    }

    /**
     * @param RA $items
     * @return RA
     */
    public function formatMany(RA $items): RA
    {
        return $items->map(function (ApiEntityInterface $item): RA {
            return $this->formatOne($item);
        });
    }

    /**
     * @param RA $groups
     * @param int $level
     * @param string $groupingField
     * @return RA
     */
    private function formatGroupped(RA $groups, int $level, string $groupingField): RA
    {
        return $groups->map(function (RA $group) use ($groupingField, $level): RA {
            return (new RA())
                ->set(ParameterEnum::GROUP_BY_AGGREGATES, $group->map(function ($value, string $field): RA {
                    $field = new Stringy($field);
                    if ($field->getPositionOfSubstring(QueryBuilderDecorator::AGGREGATE_PREFIX) !== 0) {
                        return new RA();
                    }
                    $field = $field->substring((new Stringy(QueryBuilderDecorator::AGGREGATE_PREFIX))->length());
                    $lastUnderscorePosition = $field
                        ->getPositionOfLastSubstring(QueryBuilderDecorator::AGGREGATE_FUNCTION_SEPARATOR);
                    if (null === $lastUnderscorePosition) {
                        throw new FormatterException(
                            FormatterException::EXCEPTION_INVALID_AGGREGATE_FIELD_NAME,
                            [$field]
                        );
                    }
                    $functionName = (clone $field)->substring(0, $lastUnderscorePosition);
                    $field = $field->substring($lastUnderscorePosition + 1);
                    return new RA([
                        $field => [
                            $functionName => $value,
                        ],
                    ], RA::RECURSIVE);
                }))
                ->set(ParameterEnum::GROUP_BY_FIELD, $groupingField)
                ->set(ParameterEnum::GROUP_BY_VALUE, $this->printValue($group->get(ParameterEnum::GROUP_BY_VALUE)))
                ->set(ParameterEnum::GROUP_BY_HAS_GROUPS, $level > 1)
                ->set(
                    ParameterEnum::GROUP_BY_ITEMS,
                    $this->formatListing($group->getRA(ParameterEnum::GROUP_BY_ITEMS), $level - 1)
                );
        });
    }

    /**
     * @param RA $items
     * @param int|null $level
     * @return RA
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function formatListing(RA $items, ?int $level = null): RA
    {
        $groupBy = $this->parameterResolver->getRAOrNull(ParameterEnum::GROUP_BY);
        if ($level === null) {
            $level = $groupBy !== null ? $groupBy->length() : 0;
        }
        if ($level > 0 && $groupBy !== null) {
            $levelConfiguration = $groupBy->getRAOrNull($groupBy->length() - $level) ?? new RA();
            $levelGroupingField = $levelConfiguration->hasKey(ParameterEnum::GROUP_BY_FIELD)
                ? (new RA(
                    (new Stringy($levelConfiguration->getString(ParameterEnum::GROUP_BY_FIELD)))
                        ->explode(ParameterEnum::FILTER_FIELD_SEPARATOR)
                ))->last()
                : QueryBuilderDecorator::IDENTIFIER_FIELD_NAME;
            return $this->formatGroupped($items, $level, $levelGroupingField);
        }
        return $this->formatMany($items);
    }
}

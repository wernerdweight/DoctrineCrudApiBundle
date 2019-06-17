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
    /** @var string */
    private const FIELD_SEPARATOR = '.';

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
     * @param RA $responseStructure
     * @param Stringy $path
     * @return RA|null
     */
    private function traverseResponseStructure(RA $responseStructure, Stringy $path): ?RA
    {
        $segments = new RA($path->explode(self::FIELD_SEPARATOR));
        $reducedResponseStructure = $segments->reduce(function (RA $carry, string $segment): RA {
            if (true !== $carry->hasKey($segment)) {
                return new RA();
            }
            $value = $carry->get($segment);
            if ($value instanceof RA) {
                return $value;
            }
            return new RA();
        }, $responseStructure);
        if ($reducedResponseStructure->length() === 0) {
            return null;
        }
        return $reducedResponseStructure;
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
        $root = new Stringy(ParameterEnum::EMPTY_VALUE);
        $key = (clone $field);
        $lastDotPosition = $field->getPositionOfLastSubstring(ParameterEnum::FILTER_FIELD_SEPARATOR);
        if (null !== $lastDotPosition) {
            $root = (clone $field)->substring(0, $lastDotPosition);
            $key = (clone $field)->substring($lastDotPosition + 1);
        }

        $responseStructure = $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE);
        if (null !== $responseStructure) {
            $responseStructure = $this->traverseResponseStructure($responseStructure, $root);
        }
        if (null === $responseStructure) {
            $responseStructure = $configuration->getDefaultListableFields()->fillKeys(ParameterEnum::TRUE_VALUE);
        }

        if (true === $responseStructure->hasKey((string)$key)) {
            $value = $responseStructure->get((string)$key);
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

    /**
     * @param ApiEntityInterface $item
     * @param Stringy $field
     * @param string $prefix
     * @return RA|null
     * @throws \Safe\Exceptions\StringsException
     */
    private function getRelatedEntityValue(ApiEntityInterface $item, Stringy $field, string $prefix): ?RA
    {
        $value = $this->getEntityPropertyValue($item, $field);
        return null !== $value
            ? $this->formatOne($value, \Safe\sprintf('%s%s%s', $prefix, $field, self::FIELD_SEPARATOR))
            : null;
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
            return $this->formatOne($entry, \Safe\sprintf('%s%s%s', $prefix, (string)$field, self::FIELD_SEPARATOR));
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
            return $this->getRelatedEntityValue($item, $field, $prefix);
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
            $prefixedField = new Stringy(\Safe\sprintf('%s%s', $prefix, $field));
            if (true !== $this->isAllowedForOutput($prefixedField, $configuration)) {
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
     * @param string $prefix
     * @return RA
     */
    public function formatMany(RA $items, string $prefix): RA
    {
        return $items->map(function (ApiEntityInterface $item) use ($prefix): RA {
            return $this->formatOne($item, $prefix);
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
        $prefix = (clone ($this->parameterResolver->getStringy(ParameterEnum::ENTITY_NAME)))->lowercaseFirst();
        return $this->formatMany($items, \Safe\sprintf('%s%s', (string)$prefix, self::FIELD_SEPARATOR));
    }
}

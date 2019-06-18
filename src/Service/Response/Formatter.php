<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use Doctrine\Common\Collections\Collection;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
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

    /** @var OutputVoter */
    private $outputVoter;

    /**
     * Formatter constructor.
     *
     * @param ParameterResolver    $parameterResolver
     * @param ConfigurationManager $configurationManager
     * @param OutputVoter          $outputVoter
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        ConfigurationManager $configurationManager,
        OutputVoter $outputVoter
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->configurationManager = $configurationManager;
        $this->outputVoter = $outputVoter;
    }

    /**
     * @param ApiEntityInterface $item
     * @param Stringy            $field
     * @param array              $args
     *
     * @return mixed
     */
    private function getEntityPropertyValue(ApiEntityInterface $item, Stringy $field, array $args = [])
    {
        $propertyName = (clone $field)->uppercaseFirst();
        $field = (string)$field;
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
     * @param Stringy            $field
     * @param string             $prefix
     *
     * @return RA|null
     *
     * @throws \Safe\Exceptions\StringsException
     */
    private function getRelatedEntityValue(ApiEntityInterface $item, Stringy $field, string $prefix): ?RA
    {
        $value = $this->getEntityPropertyValue($item, $field);
        return null !== $value
            ? $this->formatOne($value, \Safe\sprintf('%s%s%s', $prefix, $field, ParameterEnum::FIELD_SEPARATOR))
            : null;
    }

    /**
     * @param ApiEntityInterface      $item
     * @param Stringy                 $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param string                  $prefix
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getRelatedCollectionValue(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        string $prefix
    ): RA {
        $fieldMetadata = $configuration->getFieldMetadata((string)$field);
        $payload = $fieldMetadata && $fieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)
            ? $fieldMetadata->getRA(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)->toArray()
            : [];
        /** @var Collection $fieldValue */
        $fieldValue = $this->getEntityPropertyValue($item, $field, $payload);
        return (new RA($fieldValue->getValues()))->map(function (ApiEntityInterface $entry) use ($prefix, $field) {
            return $this->formatOne(
                $entry,
                \Safe\sprintf('%s%s%s', $prefix, (string)$field, ParameterEnum::FIELD_SEPARATOR)
            );
        });
    }

    /**
     * @param ApiEntityInterface      $item
     * @param Stringy                 $field
     * @param DoctrineCrudApiMetadata $configuration
     * @param string                  $prefix
     *
     * @return mixed
     *
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
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type) {
            return $this->getRelatedEntityValue($item, $field, $prefix);
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type) {
            return $this->getRelatedCollectionValue($item, $field, $configuration, $prefix);
        }
        throw new FormatterException(FormatterException::EXCEPTION_INVALID_METADATA_TYPE, [$type]);
    }

    /**
     * @param ApiEntityInterface $item
     * @param string             $prefix
     *
     * @return RA
     */
    public function formatOne(ApiEntityInterface $item, string $prefix = ParameterEnum::EMPTY_VALUE): RA
    {
        $configuration = $this->configurationManager->getConfigurationForEntity($item);
        $result = new RA();
        $configuration
            ->getListableFields()
            ->walk(function (string $field) use ($item, $prefix, $result, $configuration): void {
                $prefixed = new Stringy(\Safe\sprintf('%s%s', $prefix, $field));
                $responseStructure = $this->parameterResolver->getRAOrNull(ParameterEnum::RESPONSE_STRUCTURE);
                if (OutputVoter::ALLOWED !== $this->outputVoter->vote($prefixed, $configuration, $responseStructure)) {
                    return;
                }
                $metadata = $configuration->getFieldMetadata($field);
                if (null === $metadata) {
                    $result->set($field, $this->getEntityPropertyValue($item, new Stringy($field)));
                    return;
                }
                $value = $this
                    ->getEntityPropertyValueBasedOnMetadata($item, new Stringy($field), $configuration, $prefix);
                $result->set($field, $value);
            });
        return $result;
    }

    /**
     * @param RA     $items
     * @param string $prefix
     *
     * @return RA
     */
    public function formatMany(RA $items, string $prefix): RA
    {
        return $items->map(function (ApiEntityInterface $item) use ($prefix): RA {
            return $this->formatOne($item, $prefix);
        });
    }
}

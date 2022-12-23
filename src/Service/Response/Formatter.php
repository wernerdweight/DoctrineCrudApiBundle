<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\ConfigurationManager;
use WernerDweight\DoctrineCrudApiBundle\Service\Request\ParameterEnum;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class Formatter
{
    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var OutputVoter */
    private $outputVoter;

    /** @var ValueGetter */
    private $valueGetter;

    /**
     * Formatter constructor.
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        OutputVoter $outputVoter,
        ValueGetter $valueGetter
    ) {
        $this->configurationManager = $configurationManager;
        $this->outputVoter = $outputVoter;
        $this->valueGetter = $valueGetter;
    }

    /**
     * @return mixed
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function getEntityPropertyValueBasedOnMetadata(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration,
        string $prefix,
        ?RA $responseStructure
    ) {
        $type = $configuration->getFieldType((string)$field);
        if (null === $type) {
            return $this->valueGetter->getEntityPropertyValue($item, $field);
        }
        $prefix = \Safe\sprintf('%s%s%s', $prefix, $field, ParameterEnum::FIELD_SEPARATOR);
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type) {
            $value = $this->valueGetter->getRelatedEntityValue($item, $field);
            return null !== $value ? $this->format($value, $responseStructure, $prefix) : null;
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type) {
            return $this->valueGetter
                ->getRelatedCollectionValue($item, $field, $configuration)
                ->map(function (ApiEntityInterface $entry) use ($prefix, $responseStructure) {
                    return $this->format($entry, $responseStructure, $prefix);
                });
        }
        throw new FormatterException(FormatterException::EXCEPTION_INVALID_METADATA_TYPE, [$type]);
    }

    public function format(
        ApiEntityInterface $item,
        ?RA $responseStructure,
        string $prefix = ParameterEnum::EMPTY_VALUE
    ): RA {
        $configuration = $this->configurationManager->getConfigurationForEntity($item);
        $result = new RA();
        $configuration
            ->getListableFields()
            ->walk(function (string $field) use ($item, $prefix, $result, $configuration, $responseStructure): void {
                $prefixed = new Stringy(\Safe\sprintf('%s%s', $prefix, $field));
                if (OutputVoter::ALLOWED !== $this->outputVoter->vote($prefixed, $configuration, $responseStructure)) {
                    return;
                }
                $metadata = $configuration->getFieldMetadata($field);
                $fieldObject = new Stringy($field);
                if (null === $metadata) {
                    $value = $this->valueGetter->getEntityPropertyValue($item, $fieldObject);
                    if ($value instanceof \DateTime) {
                        $value = new JsonSerializableDateTime($value->format('c'));
                    }
                    $result->set($field, $value);
                    return;
                }
                $value = $this->getEntityPropertyValueBasedOnMetadata(
                    $item,
                    $fieldObject,
                    $configuration,
                    $prefix,
                    $responseStructure
                );
                $result->set($field, $value);
            });
        return $result;
    }
}

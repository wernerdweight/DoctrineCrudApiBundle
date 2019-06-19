<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

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

    /** @var ValueGetter */
    private $valueGetter;

    /**
     * Formatter constructor.
     *
     * @param ParameterResolver    $parameterResolver
     * @param ConfigurationManager $configurationManager
     * @param OutputVoter          $outputVoter
     * @param ValueGetter          $valueGetter
     */
    public function __construct(
        ParameterResolver $parameterResolver,
        ConfigurationManager $configurationManager,
        OutputVoter $outputVoter,
        ValueGetter $valueGetter
    ) {
        $this->parameterResolver = $parameterResolver;
        $this->configurationManager = $configurationManager;
        $this->outputVoter = $outputVoter;
        $this->valueGetter = $valueGetter;
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
            return $this->valueGetter->getEntityPropertyValue($item, $field);
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY === $type) {
            $value = $this->valueGetter->getRelatedEntityValue($item, $field);
            return null !== $value
                ? $this->format($value, \Safe\sprintf('%s%s%s', $prefix, $field, ParameterEnum::FIELD_SEPARATOR))
                : null;
        }
        if (DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION === $type) {
            return $this->valueGetter
                ->getRelatedCollectionValue($item, $field, $configuration)
                ->map(function (ApiEntityInterface $entry) use ($prefix, $field) {
                    return $this->format(
                        $entry,
                        \Safe\sprintf('%s%s%s', $prefix, (string)$field, ParameterEnum::FIELD_SEPARATOR)
                    );
                });
        }
        throw new FormatterException(FormatterException::EXCEPTION_INVALID_METADATA_TYPE, [$type]);
    }

    /**
     * @param ApiEntityInterface $item
     * @param string             $prefix
     *
     * @return RA
     */
    public function format(ApiEntityInterface $item, string $prefix = ParameterEnum::EMPTY_VALUE): RA
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
                    $result->set($field, $this->valueGetter->getEntityPropertyValue($item, new Stringy($field)));
                    return;
                }
                $value = $this
                    ->getEntityPropertyValueBasedOnMetadata($item, new Stringy($field), $configuration, $prefix);
                $result->set($field, $value);
            });
        return $result;
    }
}

<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\DTO;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\FilteringHelper;
use WernerDweight\DoctrineCrudApiBundle\Service\Data\QueryBuilderDecorator;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class DoctrineCrudApiMetadata
{
    /** @var string */
    private $name;

    /** @var ClassMetadata */
    private $doctrineMetadata;

    /** @var RA */
    private $apiMetadata;

    /**
     * DoctrineCrudApiMetadata constructor.
     *
     * @param string        $name
     * @param ClassMetadata $doctrineMetadata
     * @param RA            $apiMetadata
     */
    public function __construct(string $name, ClassMetadata $doctrineMetadata, RA $apiMetadata)
    {
        $this->name = $name;
        $this->doctrineMetadata = $doctrineMetadata;
        $this->apiMetadata = $apiMetadata;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        $name = new Stringy($this->getName());
        return (string)($name->substring($name->getPositionOfLastSubstring('\\') + 1));
    }

    /**
     * @return ClassMetadata
     */
    public function getDoctrineMetadata(): ClassMetadata
    {
        return $this->doctrineMetadata;
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getListableFields(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::LISTABLE);
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getDefaultListableFields(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE);
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCreatableFields(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::CREATABLE);
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getCreatableNested(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED);
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getUpdatableFields(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::UPDATABLE);
    }

    /**
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getUpdatableNested(): RA
    {
        return $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED);
    }

    /**
     * @param string  $field
     * @param RA|null $metadata
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function patchMissingFieldMetadata(string $field, ?RA $metadata): RA
    {
        $extendedFieldMetadata = $metadata ?? new RA();
        $doctrineMetadata = $this->doctrineMetadata->associationMappings[$field];
        if (true !== $extendedFieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE) ||
            null === $extendedFieldMetadata->getStringOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE)
        ) {
            $type = $doctrineMetadata[FilteringHelper::DOCTRINE_ASSOCIATION_TYPE] & ClassMetadataInfo::TO_MANY
                ? DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_COLLECTION
                : DoctrineCrudApiMappingTypeInterface::METADATA_TYPE_ENTITY;
            $extendedFieldMetadata->set(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE, $type);
        }
        if (true !== $extendedFieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS) ||
            null === $extendedFieldMetadata->getStringOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS)
        ) {
            $class = $doctrineMetadata[QueryBuilderDecorator::DOCTRINE_TARGET_ENTITY];
            $extendedFieldMetadata->set(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS, $class);
        }
        return $extendedFieldMetadata;
    }

    /**
     * @param string  $field
     * @param RA|null $metadata
     *
     * @return RA|null
     */
    private function extendFieldMetadata(string $field, ?RA $metadata = null): ?RA
    {
        if (null !== $metadata &&
            null !== $metadata->getStringOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE) &&
            null !== $metadata->getStringOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_CLASS)
        ) {
            return $metadata;
        }

        if (true === array_key_exists($field, $this->doctrineMetadata->associationMappings)) {
            return $this->patchMissingFieldMetadata($field, $metadata);
        }

        return null;
    }

    /**
     * @param string $field
     *
     * @return RA|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFieldMetadata(string $field): ?RA
    {
        $metadata = $this->apiMetadata->getRA(DoctrineCrudApiMappingTypeInterface::METADATA);
        if (true === $metadata->hasKey($field)) {
            return $this->extendFieldMetadata($field, $metadata->getRAOrNull($field));
        }
        return $this->extendFieldMetadata($field);
    }

    /**
     * @param string $field
     *
     * @return string|null
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getFieldType(string $field): ?string
    {
        $metadata = $this->getFieldMetadata($field);
        if (null !== $metadata) {
            $type = $metadata->getStringOrNull(DoctrineCrudApiMappingTypeInterface::METADATA_TYPE);
            if (null !== $type) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $field
     *
     * @return string|null
     */
    public function getInternalFieldType(string $field): ?string
    {
        $dotrineMetadata = $this->getDoctrineMetadata()->fieldMappings;
        if (true !== array_key_exists($field, $dotrineMetadata)) {
            return null;
        }
        return $dotrineMetadata[$field][DoctrineCrudApiMappingTypeInterface::METADATA_TYPE];
    }
}

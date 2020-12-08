<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use WernerDweight\RA\RA;

interface DoctrineCrudApiDriverInterface
{
    /** @var string */
    public const PROPERTY_ID = 'id';
    /** @var string */
    public const PROPERTY_FIELD = 'field';
    /** @var string */
    public const PROPERTY_ONE_TO_ONE = 'one-to-one';
    /** @var string */
    public const PROPERTY_ONE_TO_MANY = 'one-to-many';
    /** @var string */
    public const PROPERTY_MANY_TO_ONE = 'many-to-one';
    /** @var string */
    public const PROPERTY_MANY_TO_MANY = 'many-to-many';
    /** @var string */
    public const PROPERTY_ATTRIBUTE_OVERRIDES = 'attribute-overrides';
    /** @var string */
    public const PROPERTY_ATTRIBUTE_OVERRIDE = 'attribute-override';
    /** @var string */
    public const PROPERTY_UNMAPPED = 'unmapped';
    /** @var string[] */
    public const INSPECTABLE_PROPERTIES = [
        self::PROPERTY_ID,
        self::PROPERTY_FIELD,
        self::PROPERTY_ONE_TO_ONE,
        self::PROPERTY_ONE_TO_MANY,
        self::PROPERTY_MANY_TO_ONE,
        self::PROPERTY_MANY_TO_MANY,
        self::PROPERTY_UNMAPPED,
    ];

    /**
     * @return DoctrineCrudApiDriverInterface
     */
    public function setOriginalDriver(MappingDriver $driver): self;

    /**
     * @return DoctrineCrudApiDriverInterface
     */
    public function setLocator(FileLocator $locator): self;

    /**
     * @return DoctrineCrudApiDriverInterface
     */
    public function setAnnotationReader(AnnotationReader $reader): self;

    public function readMetadata(ClassMetadata $metadata, RA $config): RA;
}

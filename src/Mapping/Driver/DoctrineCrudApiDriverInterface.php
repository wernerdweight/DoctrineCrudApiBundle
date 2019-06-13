<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\ClassMetadata;
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
    /** @var string[] */
    public const INSPECTABLE_PROPERTIES = [
        self::PROPERTY_ID,
        self::PROPERTY_FIELD,
        self::PROPERTY_ONE_TO_ONE,
        self::PROPERTY_ONE_TO_MANY,
        self::PROPERTY_MANY_TO_ONE,
        self::PROPERTY_MANY_TO_MANY,
    ];

    /**
     * @param MappingDriver $driver
     * @return DoctrineCrudApiDriverInterface
     */
    public function setOriginalDriver(MappingDriver $driver): self;

    /**
     * @param FileLocator $locator
     * @return DoctrineCrudApiDriverInterface
     */
    public function setLocator(FileLocator $locator): self;

    /**
     * @param AnnotationReader $reader
     * @return DoctrineCrudApiDriverInterface
     */
    public function setAnnotationReader(AnnotationReader $reader): self;

    /**
     * @param ClassMetadata $metadata
     * @param RA $config
     * @return RA
     */
    public function readMetadata(ClassMetadata $metadata, RA $config): RA;
}

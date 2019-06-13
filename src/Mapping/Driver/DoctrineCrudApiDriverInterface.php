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

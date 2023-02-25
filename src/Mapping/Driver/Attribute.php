<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use WernerDweight\DoctrineCrudApiBundle\Exception\AnnotationDriverException;
use WernerDweight\DoctrineCrudApiBundle\Exception\XmlDriverException;
use WernerDweight\RA\RA;

final class Attribute extends AttributeDriver implements DoctrineCrudApiDriverInterface
{

    /**
     * @var MappingDriver
     */
    protected $originalDriver;

    public function __construct()
    {
        parent::__construct([]);
    }

    public function setOriginalDriver(MappingDriver $driver): DoctrineCrudApiDriverInterface
    {
        $this->originalDriver = $driver;
        return $this;
    }

    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        // TODO: use different exception
        throw new AnnotationDriverException(AnnotationDriverException::NO_LOCATOR_NEEDED);
    }

    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        // TODO: use different exception
        throw new XmlDriverException(XmlDriverException::NO_READER_NEEDED);
    }

    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        throw new Exception('Not implemented');
    }
}

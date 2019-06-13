<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\Exception\AnnotationDriverException;
use WernerDweight\RA\RA;

class Annotation extends AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /** @var AnnotationReader */
    private $annotationReader;

    /**
     * @param FileLocator $locator
     * @return Annotation
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        throw new AnnotationDriverException(AnnotationDriverException::NO_LOCATOR_NEEDED);
    }

    /**
     * @param AnnotationReader $reader
     * @return Annotation
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        $this->annotationReader = $reader;
        return $this;
    }

    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        // TODO: Implement readMetadata() method.
    }
}

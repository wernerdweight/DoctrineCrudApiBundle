<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadata;
use WernerDweight\DoctrineCrudApiBundle\Exception\AnnotationDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory\AnnotationMappingTypeFactory;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Annotation extends AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /** @var AnnotationReader */
    private $annotationReader;

    /** @var AnnotationMappingTypeFactory */
    private $mappingTypeFactory;

    /**
     * Annotation constructor.
     *
     * @param AnnotationMappingTypeFactory $mappingTypeFactory
     */
    public function __construct(AnnotationMappingTypeFactory $mappingTypeFactory)
    {
        $this->mappingTypeFactory = $mappingTypeFactory;
    }

    /**
     * @param FileLocator $locator
     *
     * @return Annotation
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        throw new AnnotationDriverException(AnnotationDriverException::NO_LOCATOR_NEEDED);
    }

    /**
     * @param AnnotationReader $reader
     *
     * @return Annotation
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        $this->annotationReader = $reader;
        return $this;
    }

    /**
     * @param \ReflectionClass $reflectedEntity
     *
     * @return bool
     *
     * @throws \Safe\Exceptions\StringsException
     */
    private function isAccessible(\ReflectionClass $reflectedEntity): bool
    {
        $accessibleAnnotation = $this->annotationReader->getClassAnnotation(
            $reflectedEntity,
            \Safe\sprintf(
                '%s\\%s',
                DoctrineCrudApiMappingTypeInterface::ANNOTATION_NAMESPACE,
                ucfirst(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE)
            )
        );
        return null !== $accessibleAnnotation;
    }

    /**
     * @param ClassMetadata $metadata
     * @param RA            $config
     *
     * @return RA
     *
     * @throws \Safe\Exceptions\StringsException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        $reflectedEntity = $metadata->getReflectionClass();

        if (true !== $this->isAccessible($reflectedEntity)) {
            return $config;
        }

        $config->set(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE, true);

        foreach ($reflectedEntity->getProperties() as $reflectedProperty) {
            foreach (DoctrineCrudApiMappingTypeInterface::MAPPING_TYPES as $mappingType) {
                $annotationClassName = \Safe\sprintf(
                    '%s\\%s',
                    DoctrineCrudApiMappingTypeInterface::ANNOTATION_NAMESPACE,
                    ucfirst($mappingType)
                );
                $annotation = $this->annotationReader->getPropertyAnnotation($reflectedProperty, $annotationClassName);
                if (null !== $annotation) {
                    $config = $this->mappingTypeFactory->get($mappingType)
                        ->readConfiguration(new Stringy($reflectedProperty->getName()), $annotation, $config);
                }
            }
        }
        return $config;
    }
}

<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
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
     */
    public function __construct(AnnotationMappingTypeFactory $mappingTypeFactory)
    {
        $this->mappingTypeFactory = $mappingTypeFactory;
    }

    /**
     * @return Annotation
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        throw new AnnotationDriverException(AnnotationDriverException::NO_LOCATOR_NEEDED);
    }

    /**
     * @return Annotation
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        $this->annotationReader = $reader;
        return $this;
    }

    /**
     * @param \ReflectionClass<ApiEntityInterface> $reflectedEntity
     *
     * @throws \Safe\Exceptions\StringsException
     */
    private function isAccessible(\ReflectionClass $reflectedEntity): bool
    {
        /** @var class-string $annotationClassName */
        $annotationClassName = \Safe\sprintf(
            '%s\\%s',
            DoctrineCrudApiMappingTypeInterface::ANNOTATION_NAMESPACE,
            ucfirst(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE)
        );
        $accessibleAnnotation = $this->annotationReader->getClassAnnotation(
            $reflectedEntity,
            $annotationClassName
        );
        return null !== $accessibleAnnotation;
    }

    /**
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
                /** @var class-string $annotationClassName */
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

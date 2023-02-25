<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use ReflectionClass;
use WernerDweight\DoctrineCrudApiBundle\Exception\AnnotationDriverException;
use WernerDweight\DoctrineCrudApiBundle\Exception\XmlDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation\Accessible;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory\AnnotationMappingTypeFactory;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Attribute extends AttributeDriver implements DoctrineCrudApiDriverInterface
{

    /**
     * @var MappingDriver
     */
    protected $originalDriver;

    /**
     * @var AnnotationMappingTypeFactory
     */
    private $mappingTypeFactory;

    public function __construct(AnnotationMappingTypeFactory $mappingTypeFactory)
    {
        parent::__construct([]);
        $this->mappingTypeFactory = $mappingTypeFactory;
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
                $attribute = $this->reader->getPropertyAttribute($reflectedProperty, $annotationClassName);
                if (null !== $attribute) {
                    $config = $this->mappingTypeFactory->get($mappingType)
                        ->readConfiguration(new Stringy($reflectedProperty->getName()), $attribute, $config);
                }
            }
        }
        return $config;
    }

    private function isAccessible(ReflectionClass $reflectedEntity): bool
    {
        $accessibleAttributes = $reflectedEntity->getAttributes(Accessible::class);
        return count($accessibleAttributes) > 0;
    }

}

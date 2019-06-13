<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use WernerDweight\DoctrineCrudApiBundle\Exception\XmlDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory\XmlMappingTypeFactory;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\MappingTypeInterface;
use WernerDweight\RA\RA;

class Xml extends AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /** @var string */
    public const WDS_NAMESPACE_URI = 'http://schemas.wds.blue/orm/doctrine-crud-api-bundle-mapping';
    /** @var string */
    private const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /** @var FileLocator */
    private $locator;

    /** @var XmlMappingTypeFactory */
    private $mappingTypeFactory;

    /**
     * Xml constructor.
     * @param XmlMappingTypeFactory $mappingTypeFactory
     */
    public function __construct(XmlMappingTypeFactory $mappingTypeFactory)
    {
        $this->mappingTypeFactory = $mappingTypeFactory;
    }

    /**
     * @param FileLocator $locator
     * @return Xml
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        $this->locator = $locator;
        return $this;
    }

    /**
     * @param AnnotationReader $reader
     * @return Xml
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        throw new XmlDriverException(XmlDriverException::NO_READER_NEEDED);
    }

    /**
     * @param string $fileName
     * @return array
     * @throws \Safe\Exceptions\SimplexmlException
     */
    private function getXmlMappingFromFile(string $fileName): array
    {
        $mapping = [];
        $xmlElement = (\Safe\simplexml_load_file($fileName))->children(self::DOCTRINE_NAMESPACE_URI);

        if (true === isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entity) {
                $entityName = (string)($entity->attributes()['name']);
                $mapping[$entityName] = $entity;
            }
            return $mapping;
        }

        if (true === isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $entityName = (string)($mappedSuperClass->attributes()['name']);
                $mapping[$entityName] = $mappedSuperClass;
            }
        }

        return $mapping;
    }

    /**
     * @param string $entityName
     * @return \SimpleXMLElement|ClassMetadata
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Safe\Exceptions\SimplexmlException
     */
    private function getXmlMapping(string $entityName): object
    {
        $originalDriver = $this->originalDriver;
        if (null !== $originalDriver && $originalDriver instanceof FileDriver) {
            return $originalDriver->getElement($entityName);
        }

        $mapping = $this->getXmlMappingFromFile($this->locator->findMappingFile($entityName));
        return $mapping[$entityName];
    }

    /**
     * @param ClassMetadata $metadata
     * @param RA $config
     * @return RA
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Safe\Exceptions\SimplexmlException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        $mapping = $this->getXmlMapping($metadata->name);
        foreach (DoctrineCrudApiDriverInterface::INSPECTABLE_PROPERTIES as $property) {
            if (true === isset($mapping->$property)) {
                foreach ($mapping->$property as $propertyMapping) {
                    $filteredMapping = $propertyMapping->children(self::WDS_NAMESPACE_URI);
                    foreach (DoctrineCrudApiMappingTypeInterface::MAPPING_TYPES as $mappingType) {
                        $config = $this->mappingTypeFactory->get($mappingType)
                            ->readConfiguration($propertyMapping, $filteredMapping, $config);
                    }
                }
            }
        }
        return $config;
    }
}

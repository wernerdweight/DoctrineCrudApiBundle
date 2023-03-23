<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use SimpleXMLElement;
use WernerDweight\DoctrineCrudApiBundle\Exception\XmlDriverException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory\XmlMappingTypeFactory;
use WernerDweight\RA\RA;

final class Xml extends AbstractDriver implements DoctrineCrudApiDriverInterface
{
    /**
     * @var string
     */
    public const WDS_NAMESPACE_URI = 'http://schemas.wds.blue/orm/doctrine-crud-api-bundle-mapping';

    /**
     * @var string
     */
    private const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * @var XmlMappingTypeFactory
     */
    private $mappingTypeFactory;

    public function __construct(XmlMappingTypeFactory $mappingTypeFactory)
    {
        $this->mappingTypeFactory = $mappingTypeFactory;
    }

    /**
     * @return Xml
     */
    public function setLocator(FileLocator $locator): DoctrineCrudApiDriverInterface
    {
        $this->locator = $locator;
        return $this;
    }

    /**
     * @return Xml
     */
    public function setAnnotationReader(AnnotationReader $reader): DoctrineCrudApiDriverInterface
    {
        throw new XmlDriverException(XmlDriverException::NO_READER_NEEDED);
    }

    /**
     * @throws \Doctrine\Persistence\Mapping\MappingException
     * @throws \Safe\Exceptions\SimplexmlException
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readMetadata(ClassMetadata $metadata, RA $config): RA
    {
        /** @var \SimpleXMLElement $mapping */
        $mapping = $this->getXmlMapping($metadata->name);

        if (true !== $this->isAccessible($mapping)) {
            return $config;
        }

        $config->set(DoctrineCrudApiMappingTypeInterface::ACCESSIBLE, true);

        $mapping = $this->extendXmlMappingWithWdsElements($mapping);

        foreach (DoctrineCrudApiDriverInterface::INSPECTABLE_PROPERTIES as $property) {
            if (true === isset($mapping[$property])) {
                $mappingProperties = is_array($mapping[$property]) ? $mapping[$property] : [$mapping[$property]];
                /** @var \SimpleXMLElement $propertyMapping */
                foreach ($mappingProperties as $propertyMapping) {
                    $filteredMapping = $propertyMapping->children(self::WDS_NAMESPACE_URI);
                    foreach (DoctrineCrudApiMappingTypeInterface::MAPPING_TYPES as $mappingType) {
                        $config = $this->mappingTypeFactory->get($mappingType)
                            ->readConfiguration($propertyMapping, $filteredMapping, $config);
                    }
                }
            }
        }

        $config = $this->readAttributeOverrides($config, $mapping);

        return $config;
    }

    /**
     * @return mixed[]
     *
     * @throws \Safe\Exceptions\SimplexmlException
     */
    private function getXmlMappingFromFile(string $fileName): array
    {
        $mapping = [];
        $xmlElement = (\Safe\simplexml_load_file($fileName))
            ->children(self::DOCTRINE_NAMESPACE_URI);

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
     * @return SimpleXMLElement|ClassMetadata
     *
     * @throws \Doctrine\Persistence\Mapping\MappingException
     * @throws \Safe\Exceptions\SimplexmlException
     */
    private function getXmlMapping(string $entityName): object
    {
        $originalDriver = $this->originalDriver;
        if (null !== $originalDriver && $originalDriver instanceof FileDriver) {
            /** @var ClassMetadata $element */
            $element = $originalDriver->getElement($entityName);
            return $element;
        }

        $mapping = $this->getXmlMappingFromFile($this->locator->findMappingFile($entityName));
        return $mapping[$entityName];
    }

    /**
     * @param SimpleXMLElement $mapping
     */
    private function isAccessible(object $mapping): bool
    {
        return array_key_exists(
            DoctrineCrudApiMappingTypeInterface::ACCESSIBLE,
            (array)($mapping->children(self::WDS_NAMESPACE_URI))
        );
    }

    /**
     * @param SimpleXMLElement $mapping
     *
     * @return mixed[]
     */
    private function extendXmlMappingWithWdsElements(object $mapping): array
    {
        $wdsElements = new RA((array)$mapping->children(self::WDS_NAMESPACE_URI));
        return array_merge((array)$mapping, $wdsElements->map(function ($element): array {
            return true !== is_array($element) ? [$element] : $element;
        })->toArray());
    }

    /**
     * @param mixed[] $mapping
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    private function readAttributeOverrides(RA $config, array $mapping): RA
    {
        if (true !== isset($mapping[DoctrineCrudApiDriverInterface::PROPERTY_ATTRIBUTE_OVERRIDES])) {
            return $config;
        }

        $overrides = $mapping[DoctrineCrudApiDriverInterface::PROPERTY_ATTRIBUTE_OVERRIDES]
            ->{DoctrineCrudApiDriverInterface::PROPERTY_ATTRIBUTE_OVERRIDE};
        foreach ($overrides as $override) {
            $filteredMapping = $override->field->children(self::WDS_NAMESPACE_URI);
            foreach (DoctrineCrudApiMappingTypeInterface::MAPPING_TYPES as $mappingType) {
                $config = $this->mappingTypeFactory->get($mappingType)
                    ->readConfiguration($override, $filteredMapping, $config);
            }
        }

        return $config;
    }
}

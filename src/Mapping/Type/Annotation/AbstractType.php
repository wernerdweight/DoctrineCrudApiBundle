<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\ORM\Mapping\MappingAttribute;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

abstract class AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param Stringy    $propertyName
     * @param MappingAttribute $annotation
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readConfiguration(object $propertyName, object $annotation, RA $config): RA
    {
        $mappingType = $this->getType();
        $config->getRA($mappingType)
            ->push((string)$propertyName);
        return $this->readExtraConfiguration($propertyName, $annotation, $config);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function readExtraConfiguration(Stringy $propertyName, MappingAttribute $annotation, RA $config): RA
    {
        return $config;
    }
}

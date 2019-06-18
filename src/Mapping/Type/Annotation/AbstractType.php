<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\Common\Annotations\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

abstract class AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param Stringy    $propertyName
     * @param Annotation $annotation
     * @param RA         $config
     *
     * @return RA
     */
    protected function readExtraConfiguration(Stringy $propertyName, Annotation $annotation, RA $config): RA
    {
        return $config;
    }

    /**
     * @param Stringy    $propertyName
     * @param Annotation $annotation
     * @param RA         $config
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function readConfiguration(object $propertyName, object $annotation, RA $config): RA
    {
        $mappingType = $this->getType();
        $config->getRA($mappingType)->push((string)$propertyName);
        return $this->readExtraConfiguration($propertyName, $annotation, $config);
    }
}

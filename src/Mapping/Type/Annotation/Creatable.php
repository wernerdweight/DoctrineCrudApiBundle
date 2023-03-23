<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\ORM\Mapping\MappingAttribute;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation\Creatable as CreatableAnnotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Creatable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::CREATABLE;
    }

    /**
     * @param CreatableAnnotation $annotation
     */
    protected function readExtraConfiguration(Stringy $propertyName, MappingAttribute $annotation, RA $config): RA
    {
        if (true === $annotation->nested) {
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED)
                ->push((string)$propertyName);
        }
        return $config;
    }
}

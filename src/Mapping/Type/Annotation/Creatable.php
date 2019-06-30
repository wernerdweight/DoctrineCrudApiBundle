<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\Common\Annotations\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Creatable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    /**
     * @param Stringy             $propertyName
     * @param UpdatableAnnotation $annotation
     * @param RA                  $config
     *
     * @return RA
     */
    protected function readExtraConfiguration(Stringy $propertyName, Annotation $annotation, RA $config): RA
    {
        if (true === $annotation->nested) {
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::CREATABLE_NESTED)
                ->push((string)$propertyName);
        }
        return $config;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::CREATABLE;
    }
}

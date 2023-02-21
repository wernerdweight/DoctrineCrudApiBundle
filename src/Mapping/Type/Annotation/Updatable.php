<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\Common\Annotations\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation\Updatable as UpdatableAnnotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Updatable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::UPDATABLE;
    }

    /**
     * @param UpdatableAnnotation $annotation
     */
    protected function readExtraConfiguration(Stringy $propertyName, Annotation $annotation, RA $config): RA
    {
        if (true === $annotation->nested) {
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::UPDATABLE_NESTED)
                ->push((string)$propertyName);
        }
        return $config;
    }
}

<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Annotation;

use Doctrine\Common\Annotations\Annotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation\Listable as ListableAnnotation;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

final class Listable extends AbstractType implements DoctrineCrudApiMappingTypeInterface
{
    public function getType(): string
    {
        return DoctrineCrudApiMappingTypeInterface::LISTABLE;
    }

    /**
     * @param ListableAnnotation $annotation
     */
    protected function readExtraConfiguration(Stringy $propertyName, Annotation $annotation, RA $config): RA
    {
        if (true === $annotation->default) {
            $config
                ->getRA(DoctrineCrudApiMappingTypeInterface::DEFAULT_LISTABLE)
                ->push((string)$propertyName);
        }
        return $config;
    }
}

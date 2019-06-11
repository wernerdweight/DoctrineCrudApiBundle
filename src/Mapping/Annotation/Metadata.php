<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Metadata extends Annotation
{
    /**
     * @Enum({"entity", "collection"})
     * @var string
     */
    public $type;

    /** @var string */
    public $class;
}

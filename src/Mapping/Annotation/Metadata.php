<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\ORM\Mapping\MappingAttribute;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Metadata implements MappingAttribute
{
    /**
     * @Enum({"entity", "collection"})
     *
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $class;

    /**
     * @var mixed[]
     */
    public $payload = [];

    public function __construct(string $type, string $class, array $payload = [])
    {
        $this->type = $type;
        $this->class = $class;
        $this->payload = $payload;
    }
}

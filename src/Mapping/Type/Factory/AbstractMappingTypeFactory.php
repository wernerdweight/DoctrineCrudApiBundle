<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\DoctrineCrudApiDriverFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Exception\DoctrineCrudApiMappingTypeFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Driver\DoctrineCrudApiDriverInterface;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

abstract class AbstractMappingTypeFactory
{
    /** @var RA */
    private $types;

    /**
     * XmlFactory constructor.
     * @param RewindableGenerator $types
     */
    public function __construct(RewindableGenerator $types)
    {
        $this->types = new RA();
        $iterator = $types->getIterator();
        while ($iterator->valid()) {
            $type = $iterator->current();
            $this->types->set($type->getType(), $type);
            $iterator->next();
        }
    }

    /**
     * @param string $type
     * @return DoctrineCrudApiMappingTypeInterface
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $type): DoctrineCrudApiMappingTypeInterface
    {
        if ($this->types->hasKey($type) !== true) {
            throw new DoctrineCrudApiMappingTypeFactoryException(
                DoctrineCrudApiMappingTypeFactoryException::INVALID_MAPPING_TYPE,
                [$type]
            );
        }
        return $this->types->get($type);
    }
}

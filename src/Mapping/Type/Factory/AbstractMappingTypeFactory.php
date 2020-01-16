<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Mapping\Type\Factory;

use Iterator;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\MappingTypeFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;

abstract class AbstractMappingTypeFactory
{
    /** @var RA */
    private $types;

    /**
     * XmlFactory constructor.
     *
     * @param RewindableGenerator<DoctrineCrudApiMappingTypeInterface> $types
     */
    public function __construct(RewindableGenerator $types)
    {
        $this->types = new RA();
        /** @var Iterator<int, DoctrineCrudApiMappingTypeInterface> $iterator */
        $iterator = $types->getIterator();
        while ($iterator->valid()) {
            $type = $iterator->current();
            $this->types->set($type->getType(), $type);
            $iterator->next();
        }
    }

    /**
     * @param string $type
     *
     * @return DoctrineCrudApiMappingTypeInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $type): DoctrineCrudApiMappingTypeInterface
    {
        if (true !== $this->types->hasKey($type)) {
            throw new MappingTypeFactoryException(
                MappingTypeFactoryException::INVALID_MAPPING_TYPE,
                [$type]
            );
        }
        return $this->types->get($type);
    }
}

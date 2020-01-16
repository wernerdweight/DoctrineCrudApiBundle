<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use WernerDweight\DoctrineCrudApiBundle\Exception\PropertyValueResolverFactoryException;
use WernerDweight\DoctrineCrudApiBundle\Service\PropertyValueResolver\Resolver\PropertyValueResolverInterface;
use WernerDweight\RA\RA;

class PropertyValueResolverFactory
{
    /** @var RA */
    private $propertyValueResolvers;

    /**
     * PropertySetterFactory constructor.
     *
     * @param RewindableGenerator<PropertyValueResolverInterface> $propertyValueResolvers
     */
    public function __construct(RewindableGenerator $propertyValueResolvers)
    {
        $this->propertyValueResolvers = new RA();
        /** @var \Generator<PropertyValueResolverInterface> $iterator */
        $iterator = $propertyValueResolvers->getIterator();
        while ($iterator->valid()) {
            /** @var PropertyValueResolverInterface $propertyValueResolver */
            $propertyValueResolver = $iterator->current();
            foreach ($propertyValueResolver->getPropertyTypes() as $type) {
                $this->propertyValueResolvers->set($type, $propertyValueResolver);
            }
            $iterator->next();
        }
    }

    /**
     * @param string $type
     *
     * @return PropertyValueResolverInterface
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function get(string $type): PropertyValueResolverInterface
    {
        if (true !== $this->propertyValueResolvers->hasKey($type)) {
            throw new PropertyValueResolverFactoryException(
                PropertyValueResolverFactoryException::INVALID_PROPERTY_TYPE,
                [$type]
            );
        }
        /** @var PropertyValueResolverInterface $propertyValueResolver */
        $propertyValueResolver = $this->propertyValueResolvers->get($type);
        return $propertyValueResolver;
    }
}

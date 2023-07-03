<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Response;

use Doctrine\Common\Collections\Collection;
use WernerDweight\DoctrineCrudApiBundle\DTO\DoctrineCrudApiMetadata;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Exception\FormatterException;
use WernerDweight\DoctrineCrudApiBundle\Mapping\Type\DoctrineCrudApiMappingTypeInterface;
use WernerDweight\RA\RA;
use WernerDweight\Stringy\Stringy;

class ValueGetter
{
    public function getEntityPropertyValue(ApiEntityInterface $item, Stringy $field, ?RA $fieldMetadata)
    {
        $payload = null !== $fieldMetadata && $fieldMetadata->hasKey(
            DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD
        )
            ? $fieldMetadata->getRA(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)->toArray()
            : [];

        // TODO: enhance payload from request etc.
        // TODO: create payload resolver object that supports '@request' etc.
        $args = $payload;

        $propertyName = (clone $field)->uppercaseFirst();
        $field = (string)$field;
        if (true === method_exists($item, 'get' . $propertyName)) {
            return $item->{'get' . $propertyName}(...$args);
        }
        if (true === method_exists($item, 'is' . $propertyName)) {
            return $item->{'is' . $propertyName}(...$args);
        }
        if (true === method_exists($item, $field)) {
            return $item->{$field}(...$args);
        }
        if (true === property_exists($item, $field)) {
            return $item->{$field};
        }
        throw new FormatterException(FormatterException::EXCEPTION_NO_PROPERTY_GETTER, [$field, get_class($item)]);
    }

    /**
     * @return mixed
     *
     * @throws \Safe\Exceptions\StringsException
     */
    public function getRelatedEntityValue(ApiEntityInterface $item, Stringy $field)
    {
        return $this->getEntityPropertyValue($item, $field, null);
    }

    /**
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRelatedCollectionValue(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration
    ): RA {
        $fieldMetadata = $configuration->getFieldMetadata((string)$field);
        /** @var Collection<int, ApiEntityInterface> $fieldValue */
        $fieldValue = $this->getEntityPropertyValue($item, $field, $fieldMetadata);
        return new RA($fieldValue->getValues());
    }
}

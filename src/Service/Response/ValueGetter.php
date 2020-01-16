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
    /**
     * @param ApiEntityInterface $item
     * @param Stringy            $field
     * @param mixed[]            $args
     *
     * @return mixed
     */
    public function getEntityPropertyValue(ApiEntityInterface $item, Stringy $field, array $args = [])
    {
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
            return $item->$field;
        }
        throw new FormatterException(
            FormatterException::EXCEPTION_NO_PROPERTY_GETTER,
            [$field, get_class($item)]
        );
    }

    /**
     * @param ApiEntityInterface $item
     * @param Stringy            $field
     *
     * @return mixed
     *
     * @throws \Safe\Exceptions\StringsException
     */
    public function getRelatedEntityValue(ApiEntityInterface $item, Stringy $field)
    {
        return $this->getEntityPropertyValue($item, $field);
    }

    /**
     * @param ApiEntityInterface      $item
     * @param Stringy                 $field
     * @param DoctrineCrudApiMetadata $configuration
     *
     * @return RA
     *
     * @throws \WernerDweight\RA\Exception\RAException
     */
    public function getRelatedCollectionValue(
        ApiEntityInterface $item,
        Stringy $field,
        DoctrineCrudApiMetadata $configuration
    ): RA {
        $fieldMetadata = $configuration->getFieldMetadata((string)$field);
        $payload = $fieldMetadata && $fieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)
            ? $fieldMetadata->getRA(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)->toArray()
            : [];
        /** @var Collection<int, ApiEntityInterface> $fieldValue */
        $fieldValue = $this->getEntityPropertyValue($item, $field, $payload);
        return new RA($fieldValue->getValues());
    }
}

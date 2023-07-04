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
    private PayloadResolver $payloadResolver;

    public function __construct(PayloadResolver $payloadResolver)
    {
        $this->payloadResolver = $payloadResolver;
    }

    public function getEntityPropertyValue(ApiEntityInterface $item, Stringy $field, ?RA $fieldMetadata)
    {
        $payload = [];
        if (null !== $fieldMetadata && $fieldMetadata->hasKey(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD)) {
            $metadataPayload = $fieldMetadata->getRA(DoctrineCrudApiMappingTypeInterface::METADATA_PAYLOAD);
            $resolvedPayload = $this->payloadResolver->resolve($metadataPayload);
            $payload = $resolvedPayload->toArray();
        }

        $propertyName = (clone $field)->uppercaseFirst();
        $field = (string)$field;
        if (true === method_exists($item, 'get' . $propertyName)) {
            return $item->{'get' . $propertyName}(...$payload);
        }
        if (true === method_exists($item, 'is' . $propertyName)) {
            return $item->{'is' . $propertyName}(...$payload);
        }
        if (true === method_exists($item, $field)) {
            return $item->{$field}(...$payload);
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

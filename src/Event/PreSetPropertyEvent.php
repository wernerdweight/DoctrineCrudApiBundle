<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

class PreSetPropertyEvent extends Event
{
    /** @var string */
    public const NAME = 'wds.doctrine_crud_api_bundle.item.pre_set_property';

    /** @var mixed */
    private $value;

    /** @var string */
    private $propertyName;

    /** @var ApiEntityInterface */
    private $item;

    /**
     * PreSetPropertyEvent constructor.
     *
     * @param mixed $value
     */
    public function __construct(ApiEntityInterface $item, string $propertyName, $value)
    {
        $this->item = $item;
        $this->propertyName = $propertyName;
        $this->value = $value;
    }

    public function getItem(): ApiEntityInterface
    {
        return $this->item;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return PreSetPropertyEvent
     */
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
}

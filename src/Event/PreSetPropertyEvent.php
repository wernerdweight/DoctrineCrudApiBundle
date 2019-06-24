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
     * @param ApiEntityInterface $item
     * @param string             $propertyName
     * @param $value
     */
    public function __construct(ApiEntityInterface $item, string $propertyName, $value)
    {
        $this->item = $item;
        $this->propertyName = $propertyName;
        $this->value = $value;
    }

    /**
     * @return ApiEntityInterface
     */
    public function getItem(): ApiEntityInterface
    {
        return $this->item;
    }

    /**
     * @return string
     */
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
}

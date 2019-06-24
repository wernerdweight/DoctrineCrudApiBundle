<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

class PrePersistEvent extends Event
{
    /** @var string */
    public const NAME = 'wds.doctrine_crud_api_bundle.item.pre_persist';

    /** @var ApiEntityInterface */
    private $item;

    /**
     * PrePersistEvent constructor.
     * @param ApiEntityInterface $item
     */
    public function __construct(ApiEntityInterface $item)
    {
        $this->item = $item;
    }

    /**
     * @return ApiEntityInterface
     */
    public function getItem(): ApiEntityInterface
    {
        return $this->item;
    }
}

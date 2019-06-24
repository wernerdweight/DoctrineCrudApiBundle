<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

class PreRemoveEvent extends Event
{
    /** @var string */
    public const NAME = 'wds.doctrine_crud_api_bundle.item.pre_remove';

    /** @var ApiEntityInterface */
    private $item;

    /**
     * PreRemoveEvent constructor.
     *
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

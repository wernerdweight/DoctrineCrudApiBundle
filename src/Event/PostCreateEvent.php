<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;

class PostCreateEvent extends Event
{
    /**
     * @var string
     */
    public const NAME = 'wds.doctrine_crud_api_bundle.item.post_create';

    /**
     * @var ApiEntityInterface
     */
    private $item;

    public function __construct(ApiEntityInterface $item)
    {
        $this->item = $item;
    }

    public function getItem(): ApiEntityInterface
    {
        return $this->item;
    }
}

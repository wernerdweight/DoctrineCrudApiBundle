<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PostCreateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PostUpdateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PrePersistEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreSetPropertyEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreUpdateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreValidateEvent;

class DoctrineCrudApiEventDispatcher
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * DoctrineCrudApiEventDispatcher constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ApiEntityInterface $item
     *
     * @return PreValidateEvent
     */
    public function dispatchPreValidate(ApiEntityInterface $item): PreValidateEvent
    {
        /** @var PreValidateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreValidateEvent($item));
        return $event;
    }

    /**
     * @param ApiEntityInterface $item
     *
     * @return PrePersistEvent
     */
    public function dispatchPrePersist(ApiEntityInterface $item): PrePersistEvent
    {
        /** @var PrePersistEvent $event */
        $event = $this->eventDispatcher->dispatch(new PrePersistEvent($item));
        return $event;
    }

    /**
     * @param ApiEntityInterface $item
     *
     * @return PostCreateEvent
     */
    public function dispatchPostCreate(ApiEntityInterface $item): PostCreateEvent
    {
        /** @var PostCreateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PostCreateEvent($item));
        return $event;
    }

    /**
     * @param ApiEntityInterface $item
     * @param string             $field
     * @param mixed              $value
     *
     * @return PreSetPropertyEvent
     */
    public function dispatchPreSetProperty(ApiEntityInterface $item, string $field, $value): PreSetPropertyEvent
    {
        /** @var PreSetPropertyEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreSetPropertyEvent($item, $field, $value));
        return $event;
    }

    /**
     * @param ApiEntityInterface $item
     * @return PreUpdateEvent
     */
    public function dispatchPreUpdate(ApiEntityInterface $item): PreUpdateEvent
    {
        /** @var PreUpdateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreUpdateEvent($item));
        return $event;
    }

    /**
     * @param ApiEntityInterface $item
     * @return PostUpdateEvent
     */
    public function dispatchPostUpdate(ApiEntityInterface $item): PostUpdateEvent
    {
        /** @var PostUpdateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PostUpdateEvent($item));
        return $event;
    }
}

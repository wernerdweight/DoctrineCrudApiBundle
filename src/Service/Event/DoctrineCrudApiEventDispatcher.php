<?php
declare(strict_types=1);

namespace WernerDweight\DoctrineCrudApiBundle\Service\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WernerDweight\DoctrineCrudApiBundle\Entity\ApiEntityInterface;
use WernerDweight\DoctrineCrudApiBundle\Event\PostCreateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PostDeleteEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PostUpdateEvent;
use WernerDweight\DoctrineCrudApiBundle\Event\PreDeleteEvent;
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
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatchPreValidate(ApiEntityInterface $item): PreValidateEvent
    {
        /** @var PreValidateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreValidateEvent($item));
        return $event;
    }

    public function dispatchPrePersist(ApiEntityInterface $item): PrePersistEvent
    {
        /** @var PrePersistEvent $event */
        $event = $this->eventDispatcher->dispatch(new PrePersistEvent($item));
        return $event;
    }

    public function dispatchPostCreate(ApiEntityInterface $item): PostCreateEvent
    {
        /** @var PostCreateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PostCreateEvent($item));
        return $event;
    }

    /**
     * @param mixed $value
     */
    public function dispatchPreSetProperty(ApiEntityInterface $item, string $field, $value): PreSetPropertyEvent
    {
        /** @var PreSetPropertyEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreSetPropertyEvent($item, $field, $value));
        return $event;
    }

    public function dispatchPreUpdate(ApiEntityInterface $item): PreUpdateEvent
    {
        /** @var PreUpdateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreUpdateEvent($item));
        return $event;
    }

    public function dispatchPostUpdate(ApiEntityInterface $item): PostUpdateEvent
    {
        /** @var PostUpdateEvent $event */
        $event = $this->eventDispatcher->dispatch(new PostUpdateEvent($item));
        return $event;
    }

    public function dispatchPreDelete(ApiEntityInterface $item): PreDeleteEvent
    {
        /** @var PreDeleteEvent $event */
        $event = $this->eventDispatcher->dispatch(new PreDeleteEvent($item));
        return $event;
    }

    public function dispatchPostDelete(ApiEntityInterface $item): PostDeleteEvent
    {
        /** @var PostDeleteEvent $event */
        $event = $this->eventDispatcher->dispatch(new PostDeleteEvent($item));
        return $event;
    }
}

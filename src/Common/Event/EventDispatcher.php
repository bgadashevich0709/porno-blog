<?php

declare(strict_types=1);

namespace App\Common\Event;

final class EventDispatcher
{
    /**
     * @var array<string, array<ListenerInterface>>
     */
    private array $listeners = [];

    public function addListener(string $eventClass, ListenerInterface $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(EventInterface $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            $listener->handle($event);
        }
    }
}

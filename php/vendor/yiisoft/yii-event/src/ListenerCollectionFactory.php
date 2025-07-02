<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\Injector\Injector;

use function is_string;

final class ListenerCollectionFactory
{
    public function __construct(
        private Injector $injector,
        private CallableFactory $callableFactory,
    ) {
    }

    /**
     * @param array $eventListeners Event listener list in format ['eventName1' => [$listener1, $listener2, ...]]
     */
    public function create(array $eventListeners): ListenerCollection
    {
        $listenerCollection = new ListenerCollection();

        foreach ($eventListeners as $eventName => $listeners) {
            if (!is_string($eventName)) {
                throw new InvalidEventConfigurationFormatException(
                    'Incorrect event listener format. Format with event name must be used.'
                );
            }

            if (!is_iterable($listeners)) {
                $type = get_debug_type($listeners);

                throw new InvalidEventConfigurationFormatException(
                    "Event listeners for $eventName must be an iterable, $type given."
                );
            }

            /** @var mixed */
            foreach ($listeners as $callable) {
                $listener =
                    /** @return mixed */
                    fn (object $event) => $this->injector->invoke(
                        $this->callableFactory->create($callable),
                        [$event]
                    );
                $listenerCollection = $listenerCollection->add($listener, $eventName);
            }
        }

        return $listenerCollection;
    }
}

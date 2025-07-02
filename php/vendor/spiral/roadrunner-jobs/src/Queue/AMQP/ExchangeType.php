<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\AMQP;

/**
 * The enum that represents the type of task delivery.
 */
enum ExchangeType: string
{
    /**
     * Used when a task needs to be delivered to specific queues. The task is
     * published to an exchanger with a specific routing key and goes to all
     * queues that are associated with this exchanger with a similar routing
     * key.
     */
    case Direct = 'direct';

    /**
     * Similarly by {@see ExchangeType::Direct} exchange enables selective
     * routing by comparing the routing key. But, in this case, the key is set
     * using a template, like "user.*.messages".
     */
    case Topics = 'topic';

    /**
     * Routes tasks to related queues based on a comparison of the (key, value)
     * pairs of the headers property of the binding and the similar property of
     * the message.
     */
    case Headers = 'headers';

    /**
     * All tasks are delivered to all queues even if a routing key is specified
     * in the task.
     */
    case Fanout = 'fanout';
}

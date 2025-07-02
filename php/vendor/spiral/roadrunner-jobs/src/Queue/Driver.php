<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

enum Driver: string
{
    /**
     * In-memory builtin RoadRunner driver.
     */
    case Memory = 'memory';

    /**
     * AMQP-based queue server implementation.
     *
     * @link https://www.rabbitmq.com/
     * @link http://activemq.apache.org/
     * @link http://qpid.apache.org/
     */
    case AMQP = 'amqp';

    case Beanstalk = 'beanstalk';

    case BoltDB = 'boltdb';

    case SQS = 'sqs';

    /**
     * @internal NOT Available: Reserved for future use.
     */
    case Redis = 'redis';

    case NATS = 'nats';

    case Kafka = 'kafka';

    /**
     * @internal NOT Available: Reserved for future use.
     */
    case NSQ = 'nsq';

    case PubSub = 'google_pub_sub';

    /**
     * @internal Used when the driver is not specified.
     */
    case Unknown = 'unknown';
}

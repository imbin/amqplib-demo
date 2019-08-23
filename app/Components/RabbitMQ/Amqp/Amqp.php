<?php

namespace app\Components\RabbitMQ\Amqp;

use Bschmitt\Amqp\Message;
use Bschmitt\Amqp\Request;
use Bschmitt\Amqp\Exception;
use Closure;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class Amqp extends Request
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var int
     */
    protected $messageCount = 0;


    /**
     *
     * Amqp constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->properties = $config;
        $this->connect();
    }

    /**
     *
     * @throws Exception\Configuration
     */
    public function setup()
    {
        $exchange = $this->getProperty('exchange');

        if (empty($exchange)) {
            throw new Exception\Configuration('Please check your settings, exchange is not defined.');
        }

        /*
            name: $exchange
            type: topic
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $this->channel->exchange_declare(
            $exchange,
            $this->getProperty('exchange_type'),
            $this->getProperty('exchange_passive'),
            $this->getProperty('exchange_durable'),
            $this->getProperty('exchange_auto_delete'),
            $this->getProperty('exchange_internal'),
            $this->getProperty('exchange_nowait'),
            $this->getProperty('exchange_properties')
        );

        $queue = $this->getProperty('queue');

        if (!empty($queue) || $this->getProperty('queue_force_declare')) {
            /*
                name: $queue
                passive: false
                durable: true // the queue will survive server restarts
                exclusive: false // queue is deleted when connection closes
                auto_delete: false //the queue won't be deleted once the channel is closed.
                nowait: false // Doesn't wait on replies for certain things.
                parameters: array // Extra data, like high availability params
            */

            /** @var ['queue name', 'message count',] queueInfo */
            $this->queueInfo = $this->channel->queue_declare(
                $queue,
                $this->getProperty('queue_passive'),
                $this->getProperty('queue_durable'),
                $this->getProperty('queue_exclusive'),
                $this->getProperty('queue_auto_delete'),
                $this->getProperty('queue_nowait'),
                $this->getProperty('queue_properties')
            );

            $this->channel->queue_bind(
                $queue ?: $this->queueInfo[0],
                $exchange,
                $this->getProperty('routing')
            );
        }
        // clear at shutdown
        $this->connection->set_close_on_destruct(true);
    }

    /**
     * @param string  $routing
     * @param Message $message
     * @throws \Exception \Configuration
     */
    public function publish($routing, $message)
    {
        $this->getChannel()->basic_publish($message, $this->getProperty('exchange'), $routing);
    }

    /**
     * @param string  $queue
     * @param \Closure $closure
     * @return bool
     * @throws \Exception
     */
    public function consume($queue, Closure $closure)
    {
        try {
            $this->messageCount = $this->getQueueMessageCount();

            if (!$this->getProperty('persistent') && $this->messageCount == 0) {
                throw new Exception\Stop();
            }

            /*
                queue: Queue from where to get the messages
                consumer_tag: Consumer identifier
                no_local: Don't receive messages published by this consumer.
                no_ack: Tells the server if the consumer will acknowledge the messages.
                exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
                nowait:
                callback: A PHP Callback
            */

            $object = $this;

            $this->getChannel()->basic_consume(
                $queue,
                $this->getProperty('consumer_tag'),
                $this->getProperty('consumer_no_local'),
                $this->getProperty('consumer_no_ack'),
                $this->getProperty('consumer_exclusive'),
                $this->getProperty('consumer_nowait'),
                function ($message) use ($closure, $object) {
                    $closure($message, $object);
                }
            );

            // consume
            while (count($this->getChannel()->callbacks)) {
                $this->getChannel()->wait(
                    null,
                    !$this->getProperty('blocking'),
                    $this->getProperty('timeout') ? $this->getProperty('timeout') : 0
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof Exception\Stop) {
                return true;
            }

            if ($e instanceof AMQPTimeoutException) {
                return true;
            }

            throw $e;
        }

        return true;
    }

    /**
     * Acknowledges a message
     *
     * @param AMQPMessage $message
     */
    public function acknowledge(AMQPMessage $message)
    {
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
    }

    /**
     * Rejects a message and requeues it if wanted (default: false)
     *
     * @param AMQPMessage $message
     * @param bool    $requeue
     */
    public function reject(AMQPMessage $message, $requeue = false)
    {
        $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], $requeue);
    }

    /**
     * Stops consumer when no message is left
     *
     * @throws Exception\Stop
     */
    public function stopWhenProcessed()
    {
        if (--$this->messageCount <= 0) {
            throw new Exception\Stop();
        }
    }


}
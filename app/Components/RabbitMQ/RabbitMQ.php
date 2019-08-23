<?php


namespace App\Components\RabbitMQ;

use Closure;
use Bschmitt\Amqp\Message;
use Bschmitt\Amqp\Request;

class RabbitMQ
{
    private static $instances = [];
    private $amqp;
    private $connectionConfig;
    private $routingKeyConfig = [];
    private $bindQueues = [];

    public static function getInstance($system)
    {
        if (!isset(self::$instances[$system])) {
            self::$instances[$system] = new self($system);
        }
        return self::$instances[$system];
    }

    private function __construct($system)
    {
        $this->connectionConfig = config('external_mq')[$system];
        $this->amqp = new Amqp\Amqp($this->connectionConfig);

        $this->routingKeyConfig = config('routing_key')[$system];
    }

    private function reconnect()
    {
        $this->amqp = new Amqp\Amqp($this->connectionConfig);
    }

    /**
     *
     * @param string $queueType
     * @param array $data
     * @param int $retryCount
     *
     * @throws \Throwable
     */
    public function publish($queueType, array $data, $retryCount = 0)
    {
        try {
            $this->bindQueue($queueType);
            $message = new Message(json_encode($data), ['content_type' => 'text/plain', 'delivery_mode' => 2]);
            $this->amqp->publish($this->routingKeyConfig[$queueType]['routing'], $message);
        } catch (\Exception $e) {
            if ($retryCount == 2) {
                throw $e;
            }
            $this->reconnect();
            $retryCount++;
            $this->publish($queueType, $data, $retryCount);
        }
    }

    /**
     *
     * @param string  $queueType
     * @param Closure $callback
     *
     * @throws \Throwable
     */
    public function consume($queueType, Closure $callback)
    {
        $this->amqp->consume($this->routingKeyConfig[$queueType]['queue'], $callback);
    }

    /**
     *
     * @param $queueType
     *
     * @throws \Throwable
     */
    private function bindQueue($queueType)
    {
        if (!isset($this->bindQueues[$queueType])) {
            $this->amqp
                ->mergeProperties($this->routingKeyConfig[$queueType])
                ->setup();
            $this->bindQueues[$queueType] = true;
        }
    }

    public function __destruct()
    {
        Request::shutdown($this->amqp->getChannel(), $this->amqp->getConnection());
    }

}
<?php

namespace App\Console\Commands\Queue;

use App\Components\RabbitMQ\RabbitMQ;
use App\Enum\QueueEnum;
use App\Traits\ExternalMQConfig;
use Illuminate\Console\Command;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

abstract class QueueBase extends Command implements QueueInterface
{
    use ExternalMQConfig;
    /**
     *
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $externalSystem = 'RABBITMQ';

    /**
     * @var string
     */
    protected $queueType = QueueEnum::QUEUE_TYPE_TEST_QUEUE;

    /**
     * @var RabbitMQ
     */
    protected $rabbitMq;

    public function preHandle()
    {
        !defined('AMQP_DEBUG') && define('AMQP_DEBUG', env('APP_DEBUG', false));
        $this->rabbitMq = RabbitMQ::getInstance($this->externalSystem);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->preHandle();
        try {
            $this->rabbitMq->consume( $this->queueType, function ( $message, $resolver ) {
                /** @var AMQPMessage $message */
                $data = $message->body;
                if ( $data ) {
                    $json = json_decode( $data, true );
                    $this->extract( $json );
                    //应答 MQ Server 已处理
                    /** @var \app\Components\RabbitMQ\Amqp\Amqp $resolver */
                    $resolver->acknowledge( $message );
                }

            });
        } catch ( AMQPRuntimeException $e ) {
            $msg = $e->getMessage();
            if ( $msg != 'Broken pipe or closed connection' ) {
                $log = [
                    'message' => $msg,
                    'params'  => [
                        'method' => __METHOD__,
                    ]
                ];
                $msg = json_encode( $log, JSON_UNESCAPED_UNICODE );
            }
            echo $msg, PHP_EOL;
            logs()->error($msg);
        } catch (\Throwable $throwable) {
            $log = [
                'message' => $throwable->getMessage(),
                'getFile' => $throwable->getFile(),
                'getLine:' => $throwable->getLine(),
            ];
            $msg = json_encode( $log, JSON_UNESCAPED_UNICODE );
            logs()->error($msg);
            echo 'MQ consumer exception, Msg:', $msg, PHP_EOL;
        }
    }

    /**
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function extract( array $data)
    {
        throw new \Exception('Unimplemented methods');
    }
}

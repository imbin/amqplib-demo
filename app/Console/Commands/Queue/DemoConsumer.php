<?php

namespace App\Console\Commands\Queue;

use App\Enum\QueueEnum;

class DemoConsumer extends QueueBase
{
    /**
     *
     *
     * @var string
     */
    protected $signature = 'queue_demo_consumer';

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


    public function extract( array $data )
    {
        echo 'start:',json_encode( $data ), PHP_EOL;
    }
}

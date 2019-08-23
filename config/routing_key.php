<?php
/**
 * 对接外部系统的MQ队列配置
 * User: zhaobin
 * Date: 2019/2/25
 */
return [
    'RABBITMQ' => [
        'test_queue' => [
            'queue' => 'test_QUEUE',
            'routing' => 'test_QUEUE'
        ],
    ],

];


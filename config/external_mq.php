<?php

return [
    'RABBITMQ' => [
        'host' => env('MQ_HOST', 'localhost'),
        'port' => env('MQ_PORT', 5672),
        'username' => env('MQ_USERNAME', ''),
        'password' => env('MQ_PASSWORD', ''),
        'vhost' => env('MQ_VHOST', '/'),
        'connect_options' => [],
        'ssl_options' => [],
        'routing' => '',
        'queue' => '',

        'exchange' => 'amq.direct',
        'exchange_type' => 'direct',
        'exchange_passive' => false,
        'exchange_durable' => true,
        'exchange_auto_delete' => false,
        'exchange_internal' => false,
        'exchange_nowait' => false,
        'exchange_properties' => [],

        'queue_force_declare' => false,
        'queue_passive' => false,
        'queue_durable' => true,
        'queue_exclusive' => false,
        'queue_auto_delete' => false,
        'queue_nowait' => false,
        //'queue_properties'      => ['x-ha-policy' => ['S', 'all']],
        'queue_properties' => [],
        'consumer_tag' => '',
        'consumer_no_local' => false,
        'consumer_no_ack' => false,
        'consumer_exclusive' => false,
        'consumer_nowait' => false,
        'timeout' => 0,
        'persistent' => true,
    ],

];


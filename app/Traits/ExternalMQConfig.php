<?php


namespace App\Traits;

trait ExternalMQConfig
{

    protected static $externalMQConfig = null;

    protected static $routingKeys = null;

    protected function initExternalMQConfig($sys)
    {
        if (empty(self::$externalMQConfig)) {
            self::$externalMQConfig = config('external_mq')[$sys];
        }
        //routing_key & queue
        if (empty(self::$routingKeys)) {
            self::$routingKeys = config('routing_key')[$sys];
        }
    }
}

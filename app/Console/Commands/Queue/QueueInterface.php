<?php


namespace App\Console\Commands\Queue;


interface QueueInterface
{
    /**
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function extract( array $data);

    /**
     *
     * @return mixed
     */
    public function preHandle();

    public function handle();
}
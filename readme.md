
## a php amqplib demo project base Laravel

- to reproduce amqp consumer cpu 99% problem

- Code file:
    app/Console/Commands/Queue/DemoConsumer.php

## config

- File: .env

```
#AFF MQ 配置
MQ_HOST=127.0.0.1
MQ_PORT=5672
MQ_USERNAME=test
MQ_PASSWORD=test
MQ_VHOST=test_host
```

## Run

```bash
php artisan queue_demo_consumer

```

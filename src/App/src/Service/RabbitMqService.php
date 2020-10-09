<?php

declare(strict_types=1);

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function newConnect(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            $this->config['host'],
            $this->config['port'],
            $this->config['user'],
            $this->config['password']
        );
    }
}
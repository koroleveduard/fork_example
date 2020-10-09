<?php

declare(strict_types=1);

namespace App;

use App\Console\Consumer;
use App\Console\Producer;
use App\Factory\Service\RabbitMqServiceFactory;
use App\Service\RabbitMqService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'console' => $this->getConsole(),
            'rabbitmq' => $this->getRabbiMqConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'invokables' => [],
            'factories'  => [
                RabbitMqService::class => RabbitMqServiceFactory::class,
            ],
        ];
    }

    public function getConsole()
    {
        return [
            'commands' => [
                Producer::class,
                Consumer::class,
            ]
        ];
    }

    public function getRabbiMqConfig()
    {
        return [
            'connection' => [
                'host' => '127.0.0.1',
                'port' => 5672,
                'user' => 'user',
                'password' => 'user',
            ]
        ];
    }
}

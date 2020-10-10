<?php

declare(strict_types=1);

namespace App\Factory\Console;

use App\Console\Consumer;
use App\Service\RabbitMqService;
use Psr\Container\ContainerInterface;

class ConsumerFactory
{
    public function __invoke(ContainerInterface $container): Consumer
    {
        $config = $container->get('config')['consumer'] ?? [];

        if (empty($config)) {
            throw new \RuntimeException('Config for rabbitmq not found!');
        }

        $workerCount = $config['worker_count'];

        return new Consumer(
            $container->get(RabbitMqService::class),
            $workerCount
        );
    }
}
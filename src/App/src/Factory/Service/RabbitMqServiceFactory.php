<?php

declare(strict_types=1);

namespace App\Factory\Service;

use App\Service\RabbitMqService;
use Psr\Container\ContainerInterface;

class RabbitMqServiceFactory
{
    public function __invoke(ContainerInterface $container) : RabbitMqService
    {
        $config = $container->get('config')['rabbitmq']['connection'] ?? [];

        if (empty($config)) {
            throw new \RuntimeException('Config for rabbitmq not found!');
        }

        return new RabbitMqService($config);
    }
}
<?php

declare(strict_types=1);

namespace App\Console;

use App\Service\RabbitMqService;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Producer extends Command
{
    const MESSAGE_LIMIT = 10000;

    private $rabbit;

    public function __construct(RabbitMqService $rabbit)
    {
        $this->rabbit = $rabbit;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:produce')
            ->setDescription('Events produce to queue');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->rabbit->newConnect();
        $channel = $connection->channel();
        $channel->queue_declare('events', false, true, false, false);

        $accountId = random_int(1,1000);;
        $batch = random_int(1,10);
        $created = 0;
        for ($i = 1; $i <= self::MESSAGE_LIMIT; $i++) {

            if ($created >= $batch) {
                $accountId = random_int(1,1000);
                $batch = random_int(1,10);
                $created = 0;
            }

            $message = [
                'account_id' => $accountId,
                'event_id' => $i
            ];

            $msg = new AMQPMessage(json_encode($message), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->basic_publish($msg, '', 'events');

            $created++;
        }

        return 0;
    }
}
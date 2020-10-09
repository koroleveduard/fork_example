<?php

declare(strict_types=1);

namespace App\Console;

use App\Service\RabbitMqService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Consumer extends Command
{
    const WORKER_COUNT = 20;
    const BASE_QUEUE_NAME = 'events';

    private $rabbit;
    private $isWorking;
    /** @var OutputInterface */
    private $output;

    public function __construct(RabbitMqService $rabbit)
    {
        $this->rabbit = $rabbit;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:consume')
            ->setDescription('Get events from queue');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->installSignalsHandler();
        $connection = $this->rabbit->newConnect();
        $channel = $connection->channel();
        $this->declareQueues($channel);
        $connection->close();
        $channel->close();

        $queueName = null;
        for ($i=0; $i < self::WORKER_COUNT; $i++) {
            $queueName = self::BASE_QUEUE_NAME . '_' . $i;
            $pid = pcntl_fork();
            if ($pid == 0) break;
        }

        if ($pid) {
            $this->consume();
        } else {
            $this->work($queueName);
        }

        return 0;
    }

    private function declareQueues(AMQPChannel $channel)
    {
        for ($i=0; $i < self::WORKER_COUNT; $i++) {
            $queueName = self::BASE_QUEUE_NAME . '_' . $i;
            $channel->queue_declare($queueName, false, true, false, false);
        }
    }

    private function consume()
    {
        $connection = $this->rabbit->newConnect();
        $channel = $connection->channel();
        $callback = function (AMQPMessage $msg) use ($channel) {
            $payload = json_decode($msg->body, true);
            $accountId = $payload['account_id'];
            $numberQueue = $accountId % self::WORKER_COUNT;
            $queueName = self::BASE_QUEUE_NAME . '_' . $numberQueue;

            $msg = new AMQPMessage(json_encode($payload), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->basic_publish($msg, '', $queueName);
        };

        $channel->basic_consume(self::BASE_QUEUE_NAME, '', false, true, false, false, $callback);

        $this->isWorking = true;
        while ($channel->is_consuming() && $this->isWorking) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function work(string $queueName)
    {
        $connection = $this->rabbit->newConnect();
        $channel = $connection->channel();

        $callback = function (AMQPMessage $msg) use ($channel) {
            $payload = json_decode($msg->body, true);
            $accountId = $payload['account_id'];
            $eventId = $payload['event_id'];
            $this->output->writeln("Account $accountId Event $eventId");
            sleep(1);
        };

        $channel->basic_consume($queueName, '', false, true, false, false, $callback);

        $this->isWorking = true;
        while ($channel->is_consuming() && $this->isWorking) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function installSignalsHandler()
    {
        pcntl_signal(SIGTERM, [$this, 'sigHandler']);
        pcntl_signal(SIGHUP,  [$this, 'sigHandler']);
        pcntl_signal(SIGINT, [$this, 'sigHandler']);
    }

    public function sigHandler($signo)
    {
        $this->output->writeln("Receive signal " . $signo);
        $this->isWorking = false;
    }
}
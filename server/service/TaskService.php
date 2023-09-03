<?php

namespace Selpol\Service;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Selpol\Container\ContainerDispose;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;

class TaskService implements LoggerAwareInterface, ContainerDispose
{
    use LoggerAwareTrait;

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public const QUEUE_HIGH = 'high';
    public const QUEUE_MEDIUM = 'medium';
    public const QUEUE_LOW = 'low';
    public const QUEUE_DEFAULT = 'default';

    private static ?TaskService $instance = null;

    public function __construct()
    {
        $this->setLogger(logger('task'));
    }

    /**
     * @throws Exception
     */
    public function connect(): void
    {
        $this->connection = new AMQPStreamConnection(config('amqp.host'), config('amqp.port'), config('amqp.username'), config('amqp.password'));
        $this->channel = $this->connection->channel();
    }

    /**
     * @throws Exception
     */
    public function enqueue(string $queue, Task $task, ?int $delay): void
    {
        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->exchange_declare('delayed_exchange', 'x-delayed-message', durable: true, arguments: new AMQPTable(['x-delayed-type' => 'direct']));

        $this->channel->queue_declare($queue, durable: true);
        $this->channel->queue_bind($queue, 'delayed_exchange', $queue);

        $message = new AMQPMessage(serialize($task), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        if ($delay)
            $message->set('application_headers', new AMQPTable(['x-delay' => $delay * 1000]));

        $this->channel->basic_publish($message, 'delayed_exchange', $queue);

        $this->logger?->info('Enqueue task', ['queue' => $queue, 'title' => $task->title, 'delay' => $delay]);
    }

    /**
     * @throws Exception
     */
    public function dequeue(string $queue, TaskCallback|callable $callback): void
    {
        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->queue_declare($queue, durable: true);

        $this->channel->basic_consume($queue, no_ack: true, callback: static function (AMQPMessage $message) use ($callback) {
            try {
                $task = unserialize($message->body);

                if ($task instanceof Task)
                    $callback($task);
            } catch (Exception $exception) {
                $this->logger?->error($exception);
            }
        });

        while ($this->channel->is_consuming())
            $this->channel->wait();
    }

    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Exception $exception) {
            $this->logger?->critical($exception);
        }
    }

    public function dispose(): void
    {
        $this->close();
    }
}
<?php

namespace Selpol\Task;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class TaskManager
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public const QUEUE_HIGH = 'high';
    public const QUEUE_MEDIUM = 'medium';
    public const QUEUE_LOW = 'low';
    public const QUEUE_DEFAULT = 'default';

    private static ?TaskManager $instance = null;

    /**
     * @throws Exception
     */
    public function connect()
    {
        $this->connection = new AMQPStreamConnection(config('amqp.host'), config('amqp.port'), config('amqp.username'), config('amqp.password'));
        $this->channel = $this->connection->channel();
    }

    /**
     * @throws Exception
     */
    public function enqueue(string $queue, Task $task, ?int $delay)
    {
        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->queue_declare($queue, durable: true);

        $message = new AMQPMessage(serialize($task), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        if ($delay)
            $message->set('application_headers', new AMQPTable(['x-delay' => $delay * 1000]));

        $this->channel->basic_publish($message, routing_key: $queue);

        logger('task')->debug('Enqueue task', ['queue' => $queue, 'title' => $task->title, 'delay' => $delay]);
    }

    /**
     * @throws Exception
     */
    public function dequeue(string $queue, TaskCallback|callable $callback)
    {
        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->queue_declare($queue, durable: true);

        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queue, no_ack: true, callback: static function (AMQPMessage $message) use ($callback) {
            try {
                $task = unserialize($message->body);

                if ($task instanceof Task)
                    $callback($task);
            } catch (Exception $exception) {
                logger('task')->error($exception);
            }
        });

        while ($this->channel->is_consuming())
            $this->channel->wait();
    }

    public function close()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Exception $exception) {
            logger('task')->critical($exception);
        }
    }

    public static function instance(): TaskManager
    {
        if (is_null(self::$instance))
            self::$instance = new TaskManager();

        return self::$instance;
    }
}
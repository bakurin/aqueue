<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

use Bakurin\AQueue\Message;
use Bakurin\AQueue\PayloadMarshaller;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class Queue implements \Bakurin\AQueue\Queue
{
    private $channel;
    private $exchangeName;
    private $exchangeType;
    private $queueName;
    private $connection;
    private $payloadMarshaller;

    public function __construct(
        AbstractConnection $connection,
        PayloadMarshaller $payloadMarshaller,
        string $queueName,
        string $exchangeName = 'router',
        string $exchangeType = 'direct'
    ) {
        if (empty($queueName)) {
            throw new \InvalidArgumentException('Queue name must be specified and must not be empty.');
        }

        $this->connection = $connection;
        $this->payloadMarshaller = $payloadMarshaller;
        $this->queueName = $queueName;
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
    }

    public function push($message)
    {
        // todo: add type casting after refactoring complete
        if (!$message instanceof \Bakurin\AQueue\Message) {
            throw new \InvalidArgumentException('Message must implement ' . \Bakurin\AQueue\Message::class);
        }

        $body = $this->payloadMarshaller->serialize($message->getPayload());
        $this->queueMessage(new AMQPMessage($body));
    }

    public function consume(callable $callback, int $timeout = 0)
    {
        $consume = function (AMQPMessage $msg) use ($callback) {
            $payload = $this->payloadMarshaller->deserialize($msg->getBody());
            $message = new Message($payload, $this->createAckCallback($msg), $this->createRequeueCallback($msg));

            $callback($message);
        };

        $this->getChannel()->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            $consume
        );

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait(null, false, $timeout);
        }
    }

    private function createRequeueCallback(AMQPMessage $msg): callable
    {
        return function (Message $message, int $attemptsLimit) use ($msg) {
            if ($msg->has('application_headers')) {
                /** @var AMQPTable $table */
                $table = $msg->get('application_headers');
                $headers = iterator_to_array($table);

                if (array_key_exists('num_retries_left', $headers)) {
                    $attemptsLimit = $headers['num_retries_left'][1] - 1;
                }
            }

            if ($attemptsLimit > 0) {
                $body = $this->payloadMarshaller->serialize($message->getPayload());
                $props = ['application_headers' => ['num_retries_left' => ['I', $attemptsLimit]]];
                $this->queueMessage(new AMQPMessage($body, $props));
            }

            $message->ack();
        };
    }

    private function createAckCallback(AMQPMessage $msg): callable
    {
        return function () use ($msg) {
            $tag = $msg->delivery_info['delivery_tag'];
            /** @var AMQPChannel $channel */
            $channel = $msg->delivery_info['channel'];
            $channel->basic_ack($tag);
        };
    }

    private function queueMessage(AMQPMessage $message)
    {
        $this->getChannel()->basic_publish($message, $this->exchangeName, $this->queueName);
    }

    private function getQueueOptions()
    {
        return [];
    }

    public function __clone()
    {
        $this->connection = clone $this->connection;
    }

    private function getChannel()
    {
        if (!$this->channel || !$this->doCacheChannel()) {
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare($this->queueName, false, true, false, false, false, $this->getQueueOptions());
            $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
            $this->channel->queue_bind($this->queueName, $this->exchangeName, $this->queueName);
        }

        return $this->channel;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    private function doCacheChannel()
    {
        return true;
    }
}

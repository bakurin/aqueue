<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

use Bakurin\AQueue\Message;
use Bakurin\AQueue\PayloadMarshaller\PayloadMarshaller;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class Queue implements \Bakurin\AQueue\Queue
{
    private $channel;
    private $connection;
    private $payloadMarshaller;
    private $config;
    private $queueName;

    public function __construct(
        string $queueName,
        AbstractConnection $connection,
        QueueConfig $config,
        PayloadMarshaller $payloadMarshaller
    ) {
        if (empty($queueName)) {
            throw new \InvalidArgumentException('Queue name must be specified and must not be empty.');
        }

        $this->queueName = $queueName;
        $this->connection = $connection;
        $this->payloadMarshaller = $payloadMarshaller;
        $this->config = $config;
    }

    public function push($messagePayload)
    {
        $body = $this->payloadMarshaller->serialize($messagePayload);
        $this->queueMessage(new AMQPMessage($body));
    }

    public function consume(callable $callback, int $timeout = 0)
    {
        $channel = $this->getChannel();
        $consume = function (AMQPMessage $msg) use ($callback) {
            $payload = $this->payloadMarshaller->deserialize($msg->getBody());
            $message = new Message(
                $payload,
                new AskCallback($msg),
                new RequeueCallback($msg, $this->payloadMarshaller, $this)
            );

            $callback($message);
        };

        $channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            $consume
        );

        while (count($channel->callbacks)) {
            $channel->wait(null, false, $timeout);
        }
    }

    public function queueMessage(AMQPMessage $message)
    {
        $this->getChannel()->basic_publish($message, $this->config->getExchangeName(), $this->queueName);
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $channel = $this->connection->channel();
            $channel->queue_declare($this->queueName, false, true, false, false, false, $this->config->getOptions());
            $channel->exchange_declare($this->config->getExchangeName(), $this->config->getExchangeType(), false, true, false);
            $channel->queue_bind($this->queueName, $this->config->getExchangeName(), $this->queueName);
            $this->channel = $channel;
        }

        return $this->channel;
    }

    public function __clone()
    {
        $this->connection = clone $this->connection;
    }
}

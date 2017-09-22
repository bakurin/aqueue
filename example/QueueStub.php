<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

use Bakurin\AQueue\Message;
use Bakurin\AQueue\PayloadMarshaller;
use Bakurin\AQueue\Queue;

final class QueueStub implements Queue
{
    private $marshaller;
    private $messages;

    public function __construct(PayloadMarshaller $marshaller)
    {
        $this->marshaller = $marshaller;
        $this->messages = [];
    }

    public function push(Message $message)
    {
        $this->messages[] = $this->marshaller->serialize($message->getPayload());
    }

    public function consume(callable $callback)
    {
        foreach ($this->messages as $message) {
            $payload = $this->marshaller->deserialize($message);
            $message = new Message($payload, function () {}, function () {});
            $callback($message);
        }
    }
}

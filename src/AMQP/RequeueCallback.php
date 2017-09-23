<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

use Bakurin\AQueue\Message;
use Bakurin\AQueue\PayloadMarshaller\PayloadMarshaller;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class RequeueCallback
{
    private $message;
    private $marshaller;
    private $queue;

    public function __construct(AMQPMessage $message, PayloadMarshaller $marshaller, Queue $queue)
    {
        $this->message = $message;
        $this->marshaller = $marshaller;
        $this->queue = $queue;
    }

    public function __invoke(Message $message, int $attemptsLimit)
    {
        if ($this->message->has('application_headers')) {
            /** @var AMQPTable $table */
            $table = $this->message->get('application_headers');
            $headers = iterator_to_array($table);

            if (array_key_exists('num_retries_left', $headers)) {
                $attemptsLimit = $headers['num_retries_left'][1] - 1;
            }
        }

        if ($attemptsLimit > 0) {
            $body = $this->marshaller->serialize($message->getPayload());
            $props = ['application_headers' => ['num_retries_left' => ['I', $attemptsLimit]]];
            $this->queue->queueMessage(new AMQPMessage($body, $props));
        }
    }
}

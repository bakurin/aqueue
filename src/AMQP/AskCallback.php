<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

final class AskCallback
{
    private $message;

    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
    }

    public function __invoke()
    {
        $tag = $this->message->delivery_info['delivery_tag'];
        /** @var AMQPChannel $channel */
        $channel = $this->message->delivery_info['channel'];
        $channel->basic_ack($tag);
    }
}

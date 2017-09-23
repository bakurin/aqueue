<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

final class Message
{
    private $payload;
    private $ack;
    private $requeue;

    public function __construct($payload, $ack = null, $requeue = null)
    {
        $this->payload = $payload;
        $this->ack = $ack;
        $this->requeue = $requeue;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function ack()
    {
        if (is_callable($this->ack)) {
            call_user_func($this->ack);
        }
    }

    public function requeue(int $attemptsLimit = 1)
    {
        if (is_callable($this->requeue)) {
            call_user_func($this->requeue, $this, $attemptsLimit);
        }
    }
}

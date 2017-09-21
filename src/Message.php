<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

final class Message
{
    private $payload;
    private $ack;
    private $requeue;

    public function __construct(array $payload, callable $ack = null, callable $requeue = null)
    {
        $this->payload = $payload;
        $this->ack = $ack ?: function () {};
        $this->requeue = $requeue ?: function () {};
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function ack()
    {
        call_user_func($this->ack);
    }

    public function requeue(int $attemptsLimit = 1)
    {
        call_user_func($this->requeue, $this, $attemptsLimit);
    }
}

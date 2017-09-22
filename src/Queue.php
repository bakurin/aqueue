<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

interface Queue
{
    public function push(Message $message);

    public function consume(callable $callback);
}

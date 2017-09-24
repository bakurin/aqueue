<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

interface Queue
{
    public function push($messagePayload);

    public function consume(callable $callback);
}

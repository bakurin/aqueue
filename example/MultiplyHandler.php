<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class MultiplyHandler
{
    public function __invoke(MultiplyMessage $message)
    {
        echo "{$message->a} * {$message->b} = " . ($message->a * $message->b) . PHP_EOL;
    }
}

<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class SummHandler
{
    public function __invoke(SummMessage $message)
    {
        echo "{$message->a} + {$message->b} = " . ($message->a + $message->b) . PHP_EOL;
    }
}

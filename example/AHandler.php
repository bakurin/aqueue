<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class AHandler
{
    public function __invoke(AMessage $message)
    {
        echo "{$message->a} + {$message->b} = " . ($message->a + $message->b) . PHP_EOL;
    }
}

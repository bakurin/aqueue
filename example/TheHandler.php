<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class TheHandler
{
    public function __invoke(TheMessage $message)
    {
        echo "{$message->a} * {$message->b} = " . ($message->a * $message->b) . PHP_EOL;
    }
}

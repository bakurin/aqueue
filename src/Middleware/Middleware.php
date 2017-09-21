<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\Message;

interface Middleware
{
    public function handle(Message $message, callable $next);
}

<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\Message;

final class MessageAckMiddleware implements Middleware
{
    public function handle(Message $message, callable $next)
    {
        $next($message);
        $message->ack();
    }
}

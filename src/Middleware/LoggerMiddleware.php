<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\Message;
use Psr\Log\LoggerInterface;

final class LoggerMiddleware implements Middleware
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Message $message, callable $next)
    {
        $start = microtime(true);
        $this->logger->info("-----------------");
        $this->logger->info("start message processing", $message->getPayload());
        $next($message);
        $this->logger->info(sprintf('message has been processed in %.3f sec', microtime(true) - $start));
    }
}

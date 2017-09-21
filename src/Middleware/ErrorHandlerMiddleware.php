<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\Message;
use Psr\Log\LoggerInterface;

final class ErrorHandlerMiddleware implements Middleware
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Message $message, callable $next)
    {
        try {
            $next($message);
        } catch (\Throwable $exception) {
            $this->logger->error("error occurred while message handling: {$exception->getMessage()}", $exception->getTrace());
        }
    }
}

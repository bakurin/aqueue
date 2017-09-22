<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\FatalError;
use Bakurin\AQueue\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ErrorHandlerMiddleware implements Middleware
{
    private $logger;
    private $munRequeueAttempts;

    public function __construct(LoggerInterface $logger = null, $numRequeueAttempts = 1)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->munRequeueAttempts = $numRequeueAttempts;
    }

    public function handle(Message $message, callable $next)
    {
        try {
            $next($message);
        } catch (FatalError $error) {
            $message->requeue($this->munRequeueAttempts);
            $this->logError($error);
        } catch (\Throwable $error) {
            $this->logError($error);
        }
    }

    private function logError(\Throwable $error)
    {
        $this->logger->error("error occurred while message handling: {$error->getMessage()}", $error->getTrace());
    }
}

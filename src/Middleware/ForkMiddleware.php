<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\FatalError;
use Bakurin\AQueue\LogicError;
use Bakurin\AQueue\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ForkMiddleware implements Middleware
{
    const STATUS_SUCCESS = 0;
    const STATUS_FATAL_ERROR = 1;
    const STATUS_LOGIC_ERROR = 2;
    const SHARED_MEMORY_SIZE = 1024 * 1024;

    private $logger;
    private $sharedMemoryKey;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->sharedMemoryKey = tempnam(sys_get_temp_dir(), 'aqueue');
    }

    public function handle(Message $message, callable $next)
    {
        $parentPid = getmypid();
        $this->logger->debug("try to fork current process PID#{$parentPid} to process message");

        $shmKey = ftok($this->sharedMemoryKey, 't');
        $shmSize = self::SHARED_MEMORY_SIZE;
        $pid = pcntl_fork();

        if (!$shmID = shmop_open($shmKey, 'c', 0666, $shmSize)) {
            $this->logger->error('could not allocate the shared memory.');
            $this->finalize(self::STATUS_FATAL_ERROR);
            return;
        }

        switch ($pid) {
            case -1:
                $this->logger->error('failed to fork.');
                $this->finalize(self::STATUS_FATAL_ERROR);
                return;
            case 0:
                $forkPid = getmypid();
                $this->logger->debug("fork PID#{$forkPid}. process the message...");
                $status = $this->processMessageInChildProcess($message, $next);
                shmop_write($shmID, $status, 0);
                posix_kill($forkPid, 9);
                return;
            default:
                $status = $this->finishFork($pid, $shmID);
                $this->finalize($status);
                return;
        }
    }

    private function finishFork(int $pid, $shmID): int
    {
        $status = 0;
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status)) {
            $this->logger->error("exited unexpected with status {$status}");

            @shmop_delete($shmID);
            @shmop_close($shmID);

            return self::STATUS_LOGIC_ERROR;
        }

        $this->logger->debug("child process was finished as expected (status: {$status})");

        $status = shmop_read($shmID, 0, 0);
        @shmop_delete($shmID);
        @shmop_close($shmID);

        return unserialize($status);
    }

    private function processMessageInChildProcess(Message $msg, callable $handler): string
    {
        try {
            $handler($msg);
            $status = self::STATUS_SUCCESS;
        } catch (\Throwable $th) {
            $class = get_class($th);
            $this->logger->error("exception [{$class}] was caught: {$th->getMessage()}", $th->getTrace());
            $status = self::STATUS_FATAL_ERROR;
        }

        return serialize($status);
    }

    private function finalize($status)
    {
        switch ($status) {
            case self::STATUS_SUCCESS:
                break;
            case self::STATUS_FATAL_ERROR:
                throw new FatalError("status: {$status}");
            case self::STATUS_LOGIC_ERROR:
                throw new LogicError("status: {$status}");
            default:
                throw new LogicError('unknown status code');
        }
    }
}

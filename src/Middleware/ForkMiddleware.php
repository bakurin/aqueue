<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Middleware;

use Bakurin\AQueue\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ForkMiddleware implements Middleware
{
    const STATUS_SUCCESS = 0;
    const STATUS_FATAL_ERROR = 1;
    const STATUS_LOGIC_ERROR = 2;

    const SHARED_MEMORY_SIZE = 1024 * 1024;
    const NUMBER_OF_FATAL_RETRIES = 5;

    private $logger;
    private $sharedMemoryKey;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->sharedMemoryKey = tempnam(sys_get_temp_dir(), 'trk');
    }

    public function handle(Message $msg, callable $next)
    {
        $parentPid = getmypid();
        $this->logger->debug("Try to fork current process PID#{$parentPid} to process message");

        $shmKey = ftok($this->sharedMemoryKey, 't');
        $shmSize = self::SHARED_MEMORY_SIZE;
        $pid = pcntl_fork();

        if (!$shmID = shmop_open($shmKey, 'c', 0666, $shmSize)) {
            $this->logger->error('Could not allocate the shared memory.');
            $this->finalize(self::STATUS_FATAL_ERROR, $msg);
            return;
        }

        if ($pid === -1) {
            $this->logger->error('Failed to fork.');
            $this->finalize(self::STATUS_FATAL_ERROR, $msg);
            return;
        }

        if ($pid === 0) {
            $forkPid = getmypid();
            $this->logger->debug("Process message in child process PID#{$forkPid}");
            $status = $this->processMessageInChildProcess($msg, $next);

            $status = serialize($status);
            shmop_write($shmID, $status, 0);

            $this->logger->debug('Child work is finish. Killing it.');
            posix_kill(getmypid(), 9);

            return;
        }

        $status = 0;
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status)) {
            $this->logger->warning("Exited unexpected with status {$status}");
            $this->logger->debug('Lets check if we need to ack the message according to the number of retries');

            $msg->requeue(self::NUMBER_OF_FATAL_RETRIES);
            @shmop_delete($shmID);
            @shmop_close($shmID);

            return;
        }

        $this->logger->debug("Child process was finished as expected (status: {$status})");

        $status = shmop_read($shmID, 0, 0);
        @shmop_delete($shmID);
        @shmop_close($shmID);

        $status = unserialize($status);
        $this->finalize($status, $msg);
    }

    protected function processMessageInChildProcess(Message $msg, callable $handler)
    {
        try {
            $handler($msg);
            return self::STATUS_SUCCESS;
        } catch (\Throwable $th) {
            $class = get_class($th);
            $this->logger->error("Exception \"{$class}\" was caught: \"{$th->getMessage()}", $th->getTrace());
            return self::STATUS_FATAL_ERROR;
        }
    }

    private function finalize($status, Message $message)
    {
        if ($status === self::STATUS_FATAL_ERROR) {
            $this->logger->error('Fatal error happened. Exiting...');
            $message->requeue(self::NUMBER_OF_FATAL_RETRIES);
            exit(1);
        } elseif ($status === self::STATUS_SUCCESS) {
            $this->logger->info('Message processed correctly.');
        } elseif ($status === self::STATUS_LOGIC_ERROR) {
            $this->logger->error('Some logic error happened.');
        }
    }
}

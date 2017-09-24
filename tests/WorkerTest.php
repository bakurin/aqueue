<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

use Bakurin\AQueue\HandlerResolver\HandlerResolver;
use Bakurin\AQueue\Middleware\Middleware;

final class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $payload = new Payload(42);
        $worker = new Worker(
            new StubQueue(new Message($payload)),
            new StubMessageFactory(),
            new StupHandlerResolver($this->createHandlerMock($payload))
        );

        $worker->appendMiddleware($this->createMiddlewareMock());

        $worker->run();
    }

    /**
     * @return Middleware
     */
    private function createMiddlewareMock()
    {
        $mock = $this->getMockBuilder(Middleware::class)
            ->setMethods(['handle'])
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Message $message, callable $next) {
                    return $next($message);
                });

        return $mock;
    }

    /**
     * @return callable
     */
    private function createHandlerMock(Payload $expectedPayload)
    {
        $mock = $this->getMockBuilder(Handler::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function (Payload $payload) use ($expectedPayload) {
                $this->assertSame($expectedPayload, $payload);
            });

        return $mock;
    }
}

class Payload
{
    public $value;

    public function __construct(int $val)
    {
        $this->value = $val;
    }
}

class StubQueue implements Queue
{
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function push($messagePayload)
    {
        throw new \RuntimeException('method is not supported');
    }

    public function consume(callable $callback)
    {
        $callback($this->message);
    }
}

class StubMessageFactory implements MessageFactory
{
    public function create($messageData)
    {
        return $messageData;
    }
}

class StupHandlerResolver implements HandlerResolver
{
    private $handler;

    public function __construct(callable $handler)
    {
        return $this->handler = $handler;
    }

    public function canResolve($event): bool
    {
        return true;
    }

    public function resolve($event): callable
    {
        return $this->handler;
    }
}

class Handler
{

}

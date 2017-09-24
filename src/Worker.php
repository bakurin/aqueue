<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

use Bakurin\AQueue\HandlerResolver\HandlerResolver;
use Bakurin\AQueue\Middleware\Middleware;

final class Worker
{
    private $handlerResolver;
    private $middlewares;
    private $queue;
    private $messageFactory;

    public function __construct(Queue $queue, MessageFactory $messageFactory, HandlerResolver $handlerResolver)
    {
        $this->queue = $queue;
        $this->messageFactory = $messageFactory;
        $this->handlerResolver = $handlerResolver;
        $this->middlewares = [];
    }

    public function appendMiddleware(Middleware $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function prependMiddleware(Middleware $middleware)
    {
        array_unshift($this->middlewares, $middleware);
    }

    public function handle(Message $message)
    {
        call_user_func($this->callableForNextMiddleware(0), $message);
    }

    private function callableForNextMiddleware($index): callable
    {
        if (!isset($this->middlewares[$index])) {
            return $this->createHandlerMiddleware();
        }

        $middleware = $this->middlewares[$index];

        return function (Message $request) use ($middleware, $index) {
            return $middleware->handle($request, $this->callableForNextMiddleware($index + 1));
        };
    }

    protected function createHandlerMiddleware(): callable
    {
        return function (Message $message) {
            $payload = $message->getPayload();
            $command = $this->messageFactory->create($payload);
            if ($command === null) {
                throw new \InvalidArgumentException('unknown message has been received from the queue');
            }

            $commandType = get_class($command);
            if (!$this->handlerResolver->canResolve($commandType)) {
                throw new \InvalidArgumentException("handler for {$commandType} is not defined.");
            }

            $handler = $this->handlerResolver->resolve($commandType);
            $handler($command);
        };
    }

    public function run()
    {
        $this->queue->consume(function (Message $message) {
            $this->handle($message);
        });
    }
}

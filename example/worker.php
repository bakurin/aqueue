<?php
declare(strict_types=1);

use Bakurin\AQueue\Example\AHandler;
use Bakurin\AQueue\Example\AMessage;
use Bakurin\AQueue\Example\JsonRpcMessageFactory;
use Bakurin\AQueue\Example\MessageHandlerResolver;
use Bakurin\AQueue\Example\QueueStub;
use Bakurin\AQueue\Example\TheHandler;
use Bakurin\AQueue\Example\TheMessage;
use Bakurin\AQueue\JsonPayloadMarshaller;
use Bakurin\AQueue\Middleware\ErrorHandlerMiddleware;
use Bakurin\AQueue\Middleware\ForkMiddleware;
use Bakurin\AQueue\Middleware\LoggerMiddleware;
use Bakurin\AQueue\Middleware\MessageAckMiddleware;
use Bakurin\AQueue\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

$pimpleContainer = new \Pimple\Container();
$pimpleContainer[AMessage::class] = function () {
    return new AHandler();
};

$pimpleContainer[TheMessage::class] = function () {
    return new TheHandler();
};

$container = new class ($pimpleContainer) implements \Psr\Container\ContainerInterface
{
    private $container;

    public function __construct(\Pimple\Container $container)
    {
        $this->container = $container;
    }

    public function get($id)
    {
        return $this->container[$id];
    }

    public function has($id)
    {
        return isset($this->container[$id]);
    }
};

$queue = new QueueStub(new JsonPayloadMarshaller());
$queue->push(json_encode(['jsonrpc' => '2.0', 'method' => 'amessage', 'params' => ['a' => 2, 'b' => 2]]));
$queue->push(json_encode(['jsonrpc' => '2.0', 'method' => 'themessage', 'params' => ['a' => 2, 'b' => 1]]));

$messageFactory = new JsonRpcMessageFactory(['amessage' => AMessage::class, 'themessage' => TheMessage::class]);
$messageHandlerResolver = new MessageHandlerResolver($container);
$logger = new class extends \Psr\Log\AbstractLogger {
    public function log($level, $message, array $context = [])
    {
        echo strtoupper($level) . ': ' . $message . PHP_EOL; // . " -- context:" . var_export($context, true) . PHP_EOL;
    }
};

$worker = new Worker($queue, $messageFactory, $messageHandlerResolver);
$worker->appendMiddleware(new ErrorHandlerMiddleware($logger));
$worker->appendMiddleware(new LoggerMiddleware($logger));
$worker->appendMiddleware(new MessageAckMiddleware());
$worker->appendMiddleware(new ForkMiddleware($logger));

$worker->run();

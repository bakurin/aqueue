<?php
declare(strict_types=1);

use Bakurin\AQueue\AMQP\Queue;
use Bakurin\AQueue\AMQP\QueueConfig;
use Bakurin\AQueue\Example\JsonRpcMessageFactory;
use Bakurin\AQueue\Example\MultiplyHandler;
use Bakurin\AQueue\Example\MultiplyMessage;
use Bakurin\AQueue\Example\SummHandler;
use Bakurin\AQueue\Example\SummMessage;
use Bakurin\AQueue\HandlerResolver\ContainerHandlerResolver;
use Bakurin\AQueue\Middleware\ErrorHandlerMiddleware;
use Bakurin\AQueue\Middleware\ForkMiddleware;
use Bakurin\AQueue\Middleware\LoggerMiddleware;
use Bakurin\AQueue\Middleware\MessageAckMiddleware;
use Bakurin\AQueue\PayloadMarshaller\JsonPayloadMarshaller;
use Bakurin\AQueue\Worker;
use PhpAmqpLib\Connection\AMQPLazyConnection;

require_once __DIR__ . '/../vendor/autoload.php';

$pimpleContainer = new \Pimple\Container();
$pimpleContainer[SummMessage::class] = function () {
    return new SummHandler();
};

$pimpleContainer[MultiplyMessage::class] = function () {
    return new MultiplyHandler();
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

$connection = new AMQPLazyConnection('localhost', 5672, 'guest', 'guest', '/');
$connection->set_close_on_destruct(false);

$queue = new Queue('test', $connection, new QueueConfig(), new JsonPayloadMarshaller());

$messageFactory = new JsonRpcMessageFactory([
    SummMessage::getMethod() => SummMessage::class,
    MultiplyMessage::getMethod() => MultiplyMessage::class
]);
$messageHandlerResolver = new ContainerHandlerResolver($container);
$logger = new class extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        echo strtoupper($level) . ': ' . $message . PHP_EOL;
    }
};

$worker = new Worker($queue, $messageFactory, $messageHandlerResolver);
$worker->appendMiddleware(new ErrorHandlerMiddleware($logger));
$worker->appendMiddleware(new LoggerMiddleware($logger));
$worker->appendMiddleware(new MessageAckMiddleware());
$worker->appendMiddleware(new ForkMiddleware($logger));

$queue->push(new SummMessage(1, 3));
$queue->push(new MultiplyMessage(2, 5));

$worker->run();

<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

use Bakurin\AQueue\MessageFactory;

final class JsonRpcMessageFactory implements MessageFactory
{
    private $map;

    public function __construct(array $messageClassMap)
    {
        $this->map = $messageClassMap;
    }

    public function create($messageData)
    {
        $method = $messageData['method'] ?? null;
        $params = $messageData['params'] ?? null;
        if ($method !== null && isset($this->map[$method])) {
            return call_user_func([$this->map[$method], 'fromArray'], $params);
        }

        throw new \RuntimeException("unable to constrict message for method [{$method}] payload");
    }
}

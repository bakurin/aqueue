<?php
declare(strict_types=1);

namespace Bakurin\AQueue\MessageFactory;

use Library\Container\HandlerResolver;

final class MessageFactory
{
    private $resolver;

    public function __construct(HandlerResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function build(array $messageData)
    {
        $name = $this->getMessageName($messageData);
        if (!empty($name) && $this->resolver->canResolve($name)) {
            $messageConstructor = $this->resolver->resolve($name);
            return call_user_func($messageConstructor, $messageData);
        }

        return null;
    }

    private function getMessageName(array $eventData): string
    {
        if (isset($eventData['jsonrpc'])) {
            return $eventData['method'];
        } else {
            if (isset($eventData['_type'])) {
                // legacy message format
                return $eventData['_type'];
            }
        }

        return '';
    }
}

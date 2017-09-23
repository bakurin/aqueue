<?php
declare(strict_types=1);

namespace Bakurin\AQueue\HandlerResolver;

use Psr\Container\ContainerInterface;

final class ContainerHandlerResolver implements HandlerResolver
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function canResolve($event): bool
    {
        return $this->container->has($event);
    }

    public function resolve($event): callable
    {
        if (!$this->canResolve($event)) {
            throw new \RuntimeException("handler is not defined for message {$event}");
        }

        return $this->container->get($event);
    }
}

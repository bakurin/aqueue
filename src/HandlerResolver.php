<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

interface HandlerResolver
{
    public function canResolve($event): bool;

    public function resolve($event): callable;
}

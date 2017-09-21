<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

interface MessageFactory
{
    public function create($messageData);
}

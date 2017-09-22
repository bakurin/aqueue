<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

interface PayloadMarshaller
{
    public function serialize($payload): string;

    public function deserialize(string $body);
}

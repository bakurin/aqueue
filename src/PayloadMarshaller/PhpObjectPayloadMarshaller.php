<?php
declare(strict_types=1);

namespace Bakurin\AQueue\PayloadMarshaller;

final class PhpObjectPayloadMarshaller implements PayloadMarshaller
{
    public function serialize($payload): string
    {
        if (!is_object($payload)) {
            throw new \InvalidArgumentException('Payload have to be a PHP object');
        }

        return serialize($payload);
    }

    public function deserialize(string $body): array
    {
        return unserialize($body);
    }
}

<?php
declare(strict_types=1);

namespace Bakurin\AQueue;

final class JsonPayloadMarshaller implements PayloadMarshaller
{
    public function serialize($payload): string
    {
        $body = json_encode($payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Unable to JSON encode');
        }

        return $body;
    }

    public function deserialize(string $body): array
    {
        $payload = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON string');
        }

        return $payload;
    }
}

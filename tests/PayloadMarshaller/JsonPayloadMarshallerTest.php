<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Tests\PayloadMarshaller;

use Bakurin\AQueue\PayloadMarshaller\JsonPayloadMarshaller;

final class JsonPayloadMarshallerTest extends \PHPUnit_Framework_TestCase
{
    public function testSerializeArray()
    {
        $marshaller = new JsonPayloadMarshaller();
        $payload = ['ok' => true];
        $serialized = $marshaller->serialize($payload);
        $this->assertEquals('{"ok":true}', $serialized);
    }

    public function testSerializeJsonSerializable()
    {
        $marshaller = new JsonPayloadMarshaller();
        $payload = new class implements \JsonSerializable
        {
            public function jsonSerialize()
            {
                return ['ok' => true];
            }
        };
        $serialized = $marshaller->serialize($payload);
        $this->assertEquals('{"ok":true}', $serialized);
    }

    public function testSerializePhpClassInstance()
    {
        $marshaller = new JsonPayloadMarshaller();
        $payload = new class
        {
            public $ok = true;
        };
        $serialized = $marshaller->serialize($payload);
        $this->assertEquals('{"ok":true}', $serialized);
    }

    public function testDeserializeJsonString()
    {
        $marshaller = new JsonPayloadMarshaller();
        $payload = '{"ok":true}';
        $result = $marshaller->deserialize($payload);
        $this->assertEquals(['ok' => true], $result);
    }

    public function testDeserializeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $marshaller = new JsonPayloadMarshaller();
        $payload = 'string';
        $result = $marshaller->deserialize($payload);
        $this->assertEquals(['string' => true], $result);
    }
}

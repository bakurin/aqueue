<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Tests\PayloadMarshaller;

use Bakurin\AQueue\PayloadMarshaller\PhpObjectPayloadMarshaller;

final class PhpObjectPayloadMarshallerTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $marshaller = new PhpObjectPayloadMarshaller();
        $payload = new Payload();
        $serialized = $marshaller->serialize($payload);
        $unserialized = $marshaller->deserialize($serialized);

        $this->assertEquals($payload, $unserialized);
    }
}

final class Payload
{
    private $prop1 = '1';
    private $prop2 = 'string';
}

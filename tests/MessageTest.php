<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Tests;

use Bakurin\AQueue\Message;

final class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPayload()
    {
        $message = new Message(123);
        $this->assertEquals(123, $message->getPayload());
    }

    public function testAck()
    {
        $ask = $this->getMockBuilder(Callback::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $ask->expects($this->once())->method('__invoke');

        $message = new Message(null, $ask);
        $message->ack();
    }

    public function testRequeue()
    {
        $attemptsLimit = 42;
        $requeue = $this->getMockBuilder(Callback::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $requeue
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function ($msg, $limit) use ($attemptsLimit) {
                $this->assertEquals($attemptsLimit, $limit);
            });

        $message = new Message(null, null, $requeue);
        $message->requeue($attemptsLimit);
    }

    public function createCallback()
    {
        $mock = $this->getMockBuilder(Callback::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $mock->expects($this->once())->method('__invoke');

        return $mock;
    }
}

class Callback
{

}

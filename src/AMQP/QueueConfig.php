<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

final class QueueConfig
{
    private $exchangeName;
    private $exchangeType;
    private $options;

    public function __construct(string $exchangeName = 'router', string $exchangeType = 'direct', array $options = [])
    {
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->options = $options;
    }

    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    public function getExchangeType(): string
    {
        return $this->exchangeType;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

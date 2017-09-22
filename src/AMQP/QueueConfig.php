<?php
declare(strict_types=1);

namespace Bakurin\AQueue\AMQP;

final class QueueConfig
{
    private $exchangeName;
    private $exchangeType;
    private $cacheChannel;
    private $options;

    public function __construct(
        string $queueName,
        string $exchangeName = 'router',
        string $exchangeType = 'direct',
        bool $cashChannel = false,
        array $options = []
    ) {
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->cacheChannel = $cashChannel;
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

    public function cacheChannel(): bool
    {
        return $this->cacheChannel;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

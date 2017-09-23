<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class SummMessage implements \JsonSerializable
{
    public $b;
    public $a;

    public function __construct(int $a, int $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public static function fromArray(array $params)
    {
        return new self((int)$params['a'], (int)$params['b']);
    }

    public function jsonSerialize()
    {
        return [
            'jsonrpc' => '2.0',
            'method' => self::getMethod(),
            'id' => null,
            'params' => [
                'a' => $this->a,
                'b' => $this->b
            ]
        ];
    }

    public static function getMethod(): string
    {
        return 'summ';
    }
}

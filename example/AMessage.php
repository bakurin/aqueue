<?php
declare(strict_types=1);

namespace Bakurin\AQueue\Example;

final class AMessage
{
    public $b;
    public $a;

    public static function fromArray(array $params)
    {
        $instance = new self();
        $instance->a = $params['a'];
        $instance->b = $params['b'];

        return $instance;
    }
}

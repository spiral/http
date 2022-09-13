<?php

declare(strict_types=1);

namespace Spiral\Tests\Http;

class Json implements \JsonSerializable
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }
}

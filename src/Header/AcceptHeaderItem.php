<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Pavel Z
 */

declare(strict_types=1);

namespace Spiral\Http\Header;

/**
 * Represents "Accept" header single item.
 *
 * Can be used for comparing each item weight or constructing the "Accept" headers.
 */
final class AcceptHeaderItem
{
    /** @var string|null */
    private $value;

    /** @var float */
    private $quality;

    /** @var array */
    private $params;

    /**
     * AcceptHeaderItem constructor.
     * @param string $mime
     * @param float  $quality
     * @param array  $params
     */
    public function __construct(string $mime, float $quality = 1.0, array $params = [])
    {
        $this->value = $mime;
        $this->quality = $quality;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $parts = [$this->getValue()];

        if ($this->quality < 1) {
            $parts[] = "q=$this->quality";
        }

        foreach ($this->getParams() as $name => $value) {
            $parts[] = "$name=$value";
        }

        return implode('; ', $parts);
    }

    /**
     * Parse accept header string.
     *
     * @param string $string
     * @return static
     */
    public static function fromString(string $string): self
    {
        $elements = explode(';', $string);

        $mime = trim((string)array_shift($elements));
        $quality = 1.0;
        $params = [];

        foreach ($elements as $element) {
            $parsed = explode('=', trim($element), 2);

            // Wrong params must be ignored
            if (count($parsed) !== 2) {
                continue;
            }

            $name = trim($parsed[0]);
            $value = trim($parsed[1]);

            if ($name === 'q') {
                $quality = (float)$value;
            } else {
                $params[$name] = $value;
            }
        }

        return new static($mime, $quality, $params);
    }


    /**
     * @param string $value
     * @return $this
     */
    public function withValue(string $value): self
    {
        $item = clone $this;
        $item->value = $value;

        return $item;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param float $quality
     * @return $this
     */
    public function withQuality(float $quality): self
    {
        $item = clone $this;
        $item->quality = $quality;

        return $item;
    }

    /**
     * @return float
     */
    public function getQuality(): float
    {
        return $this->quality;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function withParams(array $params): self
    {
        $item = clone $this;
        $item->params = $params;

        return $item;
    }
}

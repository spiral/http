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
     * Parse accept header string.
     *
     * @param string $string
     * @return static
     */
    public static function fromString(string $string): self
    {
        $elements = explode(';', $string);

        $mime = trim((string) array_shift($elements));
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
                $quality = floatval($value);
            } else {
                $params[$name] = $value;
            }
        }

        return new static($mime, $quality, $params);
    }

    /**
     * Compare to header items, witch one is preferable.
     * Return 1 if first value preferable or -1 if second, 0 in case of same weight.
     *
     * @param AcceptHeaderItem|string $a
     * @param AcceptHeaderItem|string $b
     * @return int
     */
    public static function compare($a, $b): int
    {
        $a = $a instanceof AcceptHeaderItem ? $a : AcceptHeaderItem::fromString((string) $a);
        $b = $b instanceof AcceptHeaderItem ? $b : AcceptHeaderItem::fromString((string) $b);

        if ($a->getQuality() === $b->getQuality()) {
            // If quality are same value with more params has more weight.
            if (count($a->getParams()) === count($b->getParams())) {
                // If quality and params then check for specific type or subtype.
                // Means */* or * has less weight.
                return static::compareValue($a->getValue(), $b->getValue());
            }

            return (count($a->getParams()) > count($b->getParams())) ? 1 : -1;
        }

        return ($a->getQuality() > $b->getQuality()) ? 1 : -1;
    }


    /**
     * Compare to header item values. More specific types ( with no "*" ) has more value.
     * Return 1 if first value preferable or -1 if second, 0 in case of same weight.
     *
     * @param string $a
     * @param string $b
     * @return int
     */
    private static function compareValue(string $a, string $b): int
    {
        // Check "Accept" headers values with it is type and subtype.
        if (strpos($a, '/') !== false && strpos($b, '/') !== false) {
            [$typeA, $subtypeA] = explode('/', $a, 2);
            [$typeB, $subtypeB] = explode('/', $b, 2);

            if ($typeA === $typeB) {
                return static::compareAsterisk($subtypeA, $subtypeB);
            }

            return static::compareAsterisk($typeA, $typeB);
        }

        return static::compareAsterisk($a, $b);
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    private static function compareAsterisk(string $a, string $b)
    {
        return $b === '*' && $a !== '*' ? 1
            : ($b !== '*' && $a === '*' ? -1 : 0);
    }

    /**
     * AcceptHeaderItem constructor.
     * @param string $mime
     * @param float $quality
     * @param array $params
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

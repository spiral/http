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
 * Can be used for parsing and sorting "Accept*" header items by preferable by the HTTP client.
 *
 * Supported headers:
 *   Accept
 *   Accept-Encoding
 *   Accept-Charset
 *   Accept-Language
 */
final class AcceptHeader
{
    /** @var array|AcceptHeaderItem[] */
    private $items = [];

    /** @var bool */
    private $sorted = true;

    /**
     * AcceptHeader constructor.
     * @param array|AcceptHeaderItem[]|string[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return implode(', ', $this->sorted());
    }

    /**
     * @param string $raw
     * @return AcceptHeader
     */
    public static function fromString(string $raw): self
    {
        $header = new static();
        $header->sorted = false;

        $parts = explode(',', $raw);
        foreach ($parts as $part) {
            $header->addItem(trim($part));
        }

        return $header;
    }

    /**
     * @param AcceptHeaderItem|string $item
     * @return $this
     */
    public function add($item): self
    {
        $header = clone $this;
        $header->addItem($item);

        return $header;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function has(string $value): bool
    {
        foreach ($this->items as $item) {
            if (strcasecmp($item->getValue(), trim($value)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value
     * @return AcceptHeaderItem|null
     */
    public function get(string $value): ?AcceptHeaderItem
    {
        foreach ($this->items as $item) {
            if (strcasecmp($item->getValue(), trim($value)) === 0) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return AcceptHeaderItem[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return AcceptHeaderItem[]
     */
    public function sorted(): array
    {
        $this->sort();

        return $this->items;
    }

    /**
     * Add new item to list.
     *
     * @param AcceptHeaderItem|string $item
     */
    private function addItem($item): void
    {
        $this->items[] = $item instanceof AcceptHeaderItem ? $item : AcceptHeaderItem::fromString((string)$item);
        $this->sorted = false;
    }

    /**
     * Sort header items by weight.
     */
    private function sort(): void
    {
        if (!$this->sorted) {
            /**
             * Sort item in descending order.
             */
            usort($this->items, static function (AcceptHeaderItem $a, AcceptHeaderItem $b) {
                return self::compare($a, $b) * -1;
            });

            $this->sorted = true;
        }
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
        $a = $a instanceof AcceptHeaderItem ? $a : AcceptHeaderItem::fromString((string)$a);
        $b = $b instanceof AcceptHeaderItem ? $b : AcceptHeaderItem::fromString((string)$b);

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
    private static function compareAsterisk(string $a, string $b): int
    {
        return $b === '*' && $a !== '*' ? 1
            : ($b !== '*' && $a === '*' ? -1 : 0);
    }
}

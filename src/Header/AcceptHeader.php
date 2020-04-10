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
        $this->items[] = $item instanceof AcceptHeaderItem ? $item : AcceptHeaderItem::fromString((string) $item);
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
            usort($this->items, function (AcceptHeaderItem $a, AcceptHeaderItem $b) {
                return AcceptHeaderItem::compare($a, $b) * -1;
            });

            $this->sorted = true;
        }
    }
}

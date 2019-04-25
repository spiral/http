<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http;

final class Uri extends \Zend\Diactoros\Uri implements \JsonSerializable
{
    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
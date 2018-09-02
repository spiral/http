<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http;

class Uri extends \Zend\Diactoros\Uri implements \JsonSerializable
{
    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
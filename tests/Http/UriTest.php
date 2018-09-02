<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Http\Uri;

class UriTest extends TestCase
{
    public function testJsonSerialize()
    {
        $uri = new Uri('http://google.com/hack-me?what#yes');
        $this->assertSame($uri->__toString(), $uri->jsonSerialize());
    }
}
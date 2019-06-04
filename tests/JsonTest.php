<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Sergey Telpuk
 */

namespace Spiral\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Spiral\Http\Json\JsonDecoder;
use Spiral\Http\Json\JsonEncoder;

class UriTest extends TestCase
{
    /** @var \Spiral\Http\Json\JsonDecoder */
    private $jsonDecoder;
    /** @var \Spiral\Http\Json\JsonEncoder */
    private $jsonEncoder;

    const JSON_STRING           = '[{"test":"test"}]';
    const JSON_STRING_NOT_VALID = '[{"test":"]';

    function setUp()
    {
        $this->jsonDecoder = new JsonDecoder();
        $this->jsonEncoder = new JsonEncoder();
    }

    public function testJsonDecode()
    {
        $this->assertIsArray($this->jsonDecoder->decode(self::JSON_STRING));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testJsonDecodeException()
    {
        $this->assertJson($this->jsonDecoder->decode(self::JSON_STRING_NOT_VALID));
    }
//    public function testJsonEncode()
//    {
//        $this->jsonEncoder->encode($data);
//    }
//
//    public function testJsonDecodeException()
//    {
//        $this->jsonEncoder->encode(self::JSON_STRING);
//    }
}
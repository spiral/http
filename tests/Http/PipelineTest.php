<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Http\CallableHandler;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\Pipeline;
use Spiral\Http\Tests\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequest;

class PipelineTest extends TestCase
{
    public function testTarget()
    {
        $pipeline = new Pipeline(new Container());

        $handler = new CallableHandler(function () {
            return "response";
        }, new ResponseFactory(new HttpConfig(['headers' => []])));

        $response = $pipeline->withHandler($handler)->handle(new ServerRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }

    public function testHandle()
    {
        $pipeline = new Pipeline(new Container());

        $handler = new CallableHandler(function () {
            return "response";
        }, new ResponseFactory(new HttpConfig(['headers' => []])));

        $response = $pipeline->process(new ServerRequest(), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }

    /**
     * @expectedException \Spiral\Http\Exception\PipelineException
     */
    public function testHandleException()
    {
        $pipeline = new Pipeline(new Container());
        $pipeline->handle(new ServerRequest());
    }
}
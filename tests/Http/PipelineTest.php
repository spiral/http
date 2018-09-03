<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container;
use Spiral\Http\CallableHandler;
use Spiral\Http\Pipeline;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class PipelineTest extends TestCase
{
    public function testTarget()
    {
        $pipeline = new Pipeline(new Container());

        $handler = new CallableHandler(function () {
            return "response";
        }, new ResponseFactory());

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
        }, new ResponseFactory());

        $response = $pipeline->process(new ServerRequest(), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }

    /**
     * @expectedException \Spiral\Http\Exceptions\PipelineException
     */
    public function testHandleException()
    {
        $pipeline = new Pipeline(new Container());
        $pipeline->handle(new ServerRequest());
    }
}

class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response('php://memory', $code, []))->withStatus($code, $reasonPhrase);
    }
}
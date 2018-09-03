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
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\HttpCore;
use Spiral\Http\Pipeline;
use Zend\Diactoros\ServerRequest;

class HttpCoreTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testGetPipeline()
    {
        $core = $this->getCore();
        $this->assertInstanceOf(Pipeline::class, $core->getPipeline());
    }

    public function testRunHandler()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            return "hello world";
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame("hello world", (string)$response->getBody());
    }

    /**
     * @expectedException \Spiral\Http\Exceptions\HttpException
     */
    public function testNoHandler()
    {
        $core = $this->getCore();

        $response = $core->handle(new ServerRequest());
        $this->assertSame("hello world", (string)$response->getBody());
    }

    /**
     * @expectedException \Spiral\Http\Exceptions\HttpException
     */
    public function testBadHandler()
    {
        $core = $this->getCore();
        $core->setHandler("hi");
    }

    public function testHandlerInterface()
    {
        $core = $this->getCore();
        $core->setHandler(new CallableHandler(function () {
            return "hello world";
        }, $core));

        $response = $core->handle(new ServerRequest());
        $this->assertSame("hello world", (string)$response->getBody());
    }

    public function testDefaultHeaders()
    {
        $core = $this->getCore();

        $core->setHandler(function ($req, $resp) {
            return $resp->withAddedHeader("hello", "value");
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html;charset=UTF8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
    }

    public function testOutput()
    {
        $core = $this->getCore();

        $core->setHandler(function ($req, $resp) {
            echo "hello!";
            return $resp->withAddedHeader("hello", "value");
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html;charset=UTF8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
        $this->assertSame("hello!", (string)$response->getBody());
    }

    public function testOutputAndWrite()
    {
        $core = $this->getCore();

        $core->setHandler(function ($req, $resp) {
            echo "hello!";
            $resp->getBody()->write("world ");
            return $resp->withAddedHeader("hello", "value");
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html;charset=UTF8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
        $this->assertSame("world hello!", (string)$response->getBody());
    }

    public function testNestedOutput()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            ob_start();
            ob_start();
            ob_start();
            ob_start();

            echo "hello!";
            return "world ";
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html;charset=UTF8"], $response->getHeader("Content-Type"));
        $this->assertSame("world hello!", (string)$response->getBody());
    }

    public function testJson()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            return [
                "status"  => 404,
                "message" => "not found"
            ];
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(["application/json"], $response->getHeader("Content-Type"));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPassException()
    {
        $core = $this->getCore();

        $core->setHandler(function ($req, $resp) {
            throw new \RuntimeException("error");
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html;charset=UTF8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
    }

    protected function getCore(array $middleware = []): HttpCore
    {
        return new HttpCore(
            new HttpConfig([
                'basePath'   => '/',
                'headers'    => [
                    'Content-Type' => 'text/html;charset=UTF8'
                ],
                'middleware' => $middleware,
                'cookies'    => [
                    'domain'   => '.%s',
                    'method'   => HttpConfig::COOKIE_ENCRYPT,
                    'excluded' => ['PHPSESSID', 'csrf-token']
                ],
                'csrf'       => [
                    'cookie'   => 'csrf-token',
                    'length'   => 16,
                    'lifetime' => 86400
                ]
            ]),
            new Pipeline($this->container),
            $this->container
        );
    }
}
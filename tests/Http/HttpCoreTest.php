<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Core\Container;
use Spiral\Http\CallableHandler;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\HttpCore;
use Spiral\Http\Pipeline;
use Spiral\Http\ResponseFactory;
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
        }, new TestFactory()));

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
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
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
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
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
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
        $this->assertSame("world hello!", (string)$response->getBody());
    }

    public function testNestedOutput()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            ob_start();
            ob_start();
            echo "hello!";
            ob_start();
            ob_start();

            return "world ";
        });

        $this->assertSame(1, ob_get_level());
        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame("world hello!", (string)$response->getBody());
        $this->assertSame(1, ob_get_level());
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

    public function testJsonSerializable()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            return new Json([
                "status"  => 404,
                "message" => "not found"
            ]);
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(["application/json"], $response->getHeader("Content-Type"));
    }

    public function testMiddleware()
    {
        $core = $this->getCore([HeaderMiddleware::class]);

        $core->setHandler(function () {
            return "hello?";
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame(["Value*"], $response->getHeader("header"));
        $this->assertSame("hello?", (string)$response->getBody());
    }

    public function testMiddlewareTrait()
    {
        $core = $this->getCore();

        $core->getPipeline()->pushMiddleware(new Header2Middleware());
        $core->getPipeline()->riseMiddleware(new HeaderMiddleware());

        $core->setHandler(function () {
            return "hello?";
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame(["Value+", "Value*"], $response->getHeader("header"));
        $this->assertSame("hello?", (string)$response->getBody());
    }

    public function testMiddlewareTraitReversed()
    {
        $core = $this->getCore();

        $core->getPipeline()->pushMiddleware(new HeaderMiddleware());
        $core->getPipeline()->riseMiddleware(new Header2Middleware());

        $core->setHandler(function () {
            return "hello?";
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame(["Value*", "Value+"], $response->getHeader("header"));
        $this->assertSame("hello?", (string)$response->getBody());
    }

    public function testScope()
    {
        $core = $this->getCore();

        $core->setHandler(function () {
            $this->assertTrue($this->container->has(ServerRequestInterface::class));

            return 'OK';
        });

        $response = $core->handle(new ServerRequest());
        $this->assertSame("OK", (string)$response->getBody());
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
        $this->assertSame(["text/html;charset=UTF-8"], $response->getHeader("Content-Type"));
        $this->assertSame(["value"], $response->getHeader("hello"));
    }

    protected function getCore(array $middleware = []): HttpCore
    {
        $config = new HttpConfig([
            'basePath'   => '/',
            'headers'    => [
                'Content-Type' => 'text/html; charset=UTF-8'
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
        ]);

        return new HttpCore(
            $config,
            new Pipeline($this->container),
            new ResponseFactory($config),
            $this->container
        );
    }
}

class HeaderMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request)->withAddedHeader("Header", "Value*");
    }
}

class Header2Middleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request)->withAddedHeader("Header", "Value+");
    }
}

class Json implements \JsonSerializable
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
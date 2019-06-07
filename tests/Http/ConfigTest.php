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
use Spiral\Http\Config\HttpConfig;

class ConfigTest extends TestCase
{
    public function testBasePath()
    {
        $c = new HttpConfig([
            'basePath' => '/'
        ]);

        $this->assertSame('/', $c->getBasePath());
    }

    public function testBaseHeaders()
    {
        $c = new HttpConfig([
            'headers' => [
                'key' => 'value'
            ]
        ]);

        $this->assertSame(['key' => 'value'], $c->getBaseHeaders());
    }

    public function testBaseMiddleware()
    {
        $c = new HttpConfig([
            'middleware' => [TestMiddleware::class]
        ]);

        $this->assertSame([TestMiddleware::class], $c->getMiddleware());
    }

    public function testBaseMiddlewareFallback()
    {
        $c = new HttpConfig([
            'middlewares' => [TestMiddleware::class]
        ]);

        $this->assertSame([TestMiddleware::class], $c->getMiddleware());
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\Middleware\CookieMiddleware;
use Spiral\Http\Uri;

class ConfigTest extends TestCase
{
    public function testBasePath()
    {
        $c = new HttpConfig([
            'basePath' => '/'
        ]);

        $this->assertSame('/', $c->basePath());
    }

    public function testBaseHeaders()
    {
        $c = new HttpConfig([
            'headers' => [
                'key' => 'value'
            ]
        ]);

        $this->assertSame(['key' => 'value'], $c->baseHeaders());
    }

    public function testBaseMiddleware()
    {
        $c = new HttpConfig([
            'middleware' => [CookieMiddleware::class]
        ]);

        $this->assertSame([CookieMiddleware::class], $c->baseMiddleware());
    }

    public function testBaseMiddlewareFallback()
    {
        $c = new HttpConfig([
            'middlewares' => [CookieMiddleware::class]
        ]);

        $this->assertSame([CookieMiddleware::class], $c->baseMiddleware());
    }

    public function testCsrf()
    {
        $c = new HttpConfig([
            'csrf' => [
                'cookie'   => 'csrf-token',
                'length'   => 16,
                'lifetime' => 86400
            ]
        ]);

        $this->assertSame('csrf-token', $c->csrfCookie());
        $this->assertSame(16, $c->csrfLength());
        $this->assertSame(86400, $c->csrfLifetime());

        $c = new HttpConfig([
            'csrf' => [
                'cookie' => 'csrf-token',
                'length' => 16
            ]
        ]);

        $this->assertSame(null, $c->csrfLifetime());
    }

    public function testCookies()
    {
        $c = new HttpConfig([
            'cookies' => [
                'domain'   => '.%s',
                'method'   => HttpConfig::COOKIE_ENCRYPT,
                'excluded' => ['PHPSESSID', 'csrf-token']
            ],
        ]);

        $this->assertSame(HttpConfig::COOKIE_ENCRYPT, $c->cookieProtectionMethod());
        $this->assertSame(['PHPSESSID', 'csrf-token'], $c->cookiesExcluded());
    }

    public function testCookieDomain()
    {
        $c = new HttpConfig([
            'cookies' => [
                'domain' => '.%s',
            ],
        ]);

        $this->assertSame('.domain.com', $c->cookieDomain(new Uri('http://domain.com/')));
        $this->assertSame('.domain.com', $c->cookieDomain(new Uri('https://domain.com/')));
        $this->assertSame('.domain.com:9090', $c->cookieDomain(new Uri('https://domain.com:9090/')));
        $this->assertSame(null, $c->cookieDomain(new Uri('/')));
        $this->assertSame('.localhost:9090', $c->cookieDomain(new Uri('localhost:9090/')));

        $this->assertSame('192.169.1.10:8080', $c->cookieDomain(new Uri('http://192.169.1.10:8080/')));

        $c = new HttpConfig([
            'cookies' => [
                'domain' => '.doo.com',
            ],
        ]);

        $this->assertSame('.doo.com', $c->cookieDomain(new Uri('http://domain.com/')));
    }
}
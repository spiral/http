<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container;
use Spiral\Encrypter\Encrypter;
use Spiral\Encrypter\EncrypterInterface;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\Cookies\CookieQueue;
use Spiral\Http\HttpCore;
use Spiral\Http\Middleware\CookiesMiddleware;
use Spiral\Http\Pipeline;
use Zend\Diactoros\ServerRequest;

class CookiesTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->bind(HttpConfig::class, new HttpConfig([
            'basePath'   => '/',
            'headers'    => [
                'Content-Type' => 'text/html; charset=UTF-8'
            ],
            'middleware' => [],
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
        ]));

        $this->container->bind(EncrypterInterface::class, new Encrypter(
            Key::createNewRandomKey()->saveToAsciiSafeString()
        ));
    }

    public function testScope()
    {
        $core = $this->getCore([CookiesMiddleware::class]);
        $core->setHandler(function ($r) {

            $this->assertInstanceOf(
                CookieQueue::class,
                $this->container->get(CookieQueue::class)
            );

            $this->assertSame(
                $this->container->get(CookieQueue::class),
                $r->getAttribute(CookieQueue::ATTRIBUTE)
            );

            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    public function testSetEncryptedCookie()
    {
        $core = $this->getCore([CookiesMiddleware::class]);
        $core->setHandler(function ($r) {
            $this->container->get(CookieQueue::class)->set('name', 'value');
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);
        $this->assertArrayHasKey('name', $cookies);
        $this->assertSame('value', $this->container->get(EncrypterInterface::class)->decrypt($cookies['name']));
    }


    public function testDelete()
    {
        $core = $this->getCore([CookiesMiddleware::class]);
        $core->setHandler(function ($r) {
            $this->container->get(CookieQueue::class)->set('name', 'value');
            $this->container->get(CookieQueue::class)->delete('name');

            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);
        $this->assertArrayHasKey('name', $cookies);
        $this->assertSame('', $cookies['name']);
    }

    protected function getCore(array $middleware = []): HttpCore
    {
        return new HttpCore(
            new HttpConfig([
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
            ]),
            new Pipeline($this->container),
            $this->container
        );
    }

    protected function get(
        HttpCore $core,
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $core->handle($this->request($uri, 'GET', $query, $headers, $cookies));
    }

    protected function request(
        $uri,
        string $method,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ServerRequest {
        return new ServerRequest(
            [],
            [],
            $uri,
            $method,
            'php://input',
            $headers, $cookies,
            $query
        );
    }

    protected function fetchCookies(ResponseInterface $response)
    {
        $result = [];

        foreach ($response->getHeaders() as $line) {
            $cookie = explode('=', join("", $line));
            $result[$cookie[0]] = rawurldecode(substr($cookie[1], 0, strpos($cookie[1], ';')));
        }

        return $result;
    }
}
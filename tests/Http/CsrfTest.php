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
use Spiral\Encrypter\Config\EncrypterConfig;
use Spiral\Encrypter\EncrypterFactory;
use Spiral\Encrypter\EncryptionInterface;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\HttpCore;
use Spiral\Http\Middleware\CookiesMiddleware;
use Spiral\Http\Middleware\CsrfFirewall;
use Spiral\Http\Middleware\CsrfMiddleware;
use Spiral\Http\Pipeline;
use Spiral\Http\ResponseFactory;
use Zend\Diactoros\ServerRequest;

class CsrfTest extends TestCase
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

        $this->container->bind(
            EncryptionInterface::class,
            new EncrypterFactory(new EncrypterConfig([
                'key' => Key::createNewRandomKey()->saveToAsciiSafeString()
            ]))
        );
    }

    public function testGet()
    {
        $core = $this->getCore([CsrfMiddleware::class]);
        $core->setHandler(function ($r) {
            return $r->getAttribute(CsrfMiddleware::ATTRIBUTE);
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());

        $cookies = $this->fetchCookies($response);

        $this->assertArrayHasKey('csrf-token', $cookies);
        $this->assertSame($cookies['csrf-token'], (string)$response->getBody());

    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLengthException()
    {
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
                'length'   => 0,
                'lifetime' => 86400
            ]
        ]));

        $core = $this->getCore([CsrfMiddleware::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
    }

    public function testPostForbidden()
    {
        $core = $this->getCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->post($core, '/');
        $this->assertSame(412, $response->getStatusCode());
    }

    /**
     * @expectedException \LogicException
     */
    public function testLogicException()
    {
        $core = $this->getCore([CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->post($core, '/');
    }

    public function testPostOK()
    {
        $core = $this->getCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);

        $response = $this->post($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->post($core, '/', [
            'csrf-token' => $cookies['csrf-token']
        ], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    public function testHeaderOK()
    {
        $core = $this->getCore([CsrfMiddleware::class, CsrfFirewall::class]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);

        $response = $this->post($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->post($core, '/', [], [
            'X-CSRF-Token' => $cookies['csrf-token']
        ], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
    }

    public function testPostOKCookieManagerEnabled()
    {
        $core = $this->getCore([
            CookiesMiddleware::class,
            CsrfMiddleware::class,
            CsrfFirewall::class
        ]);
        $core->setHandler(function () {
            return 'all good';
        });

        $response = $this->get($core, '/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());

        $cookies = $this->fetchCookies($response);

        $response = $this->post($core, '/', [], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(412, $response->getStatusCode());

        $response = $this->post($core, '/', [
            'csrf-token' => $cookies['csrf-token']
        ], [], ['csrf-token' => $cookies['csrf-token']]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('all good', (string)$response->getBody());
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

    protected function get(
        HttpCore $core,
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $core->handle($this->request($uri, 'GET', $query, $headers, $cookies));
    }

    protected function post(
        HttpCore $core,
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $core->handle(
            $this->request($uri, 'POST', [], $headers, $cookies)->withParsedBody($data)
        );
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
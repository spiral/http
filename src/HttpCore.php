<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

class HttpCore implements
    ServerRequestFactoryInterface,
    ResponseFactoryInterface,
    RequestHandlerInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var \Zend\HttpHandlerRunner\Emitter\EmitterInterface */
    private $emitter;

    /**
     * @param ContainerInterface $container Https requests are executed in a container scopes.
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param EmitterInterface $emitter
     *
     * @return HttpCore
     */
    public function setEmitter(EmitterInterface $emitter): HttpCore
    {
        $this->emitter = $emitter;

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Implement handle() method.
    }

    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return new Request($uri, $method);
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response('php://memory', $code, []))->withStatus($code, $reasonPhrase);
    }
}
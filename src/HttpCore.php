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
use Zend\Diactoros\Response;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

class HttpCore implements ResponseFactoryInterface
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

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response('php://memory', $code, []);
    }
}
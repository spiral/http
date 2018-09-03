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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\Exceptions\HttpException;
use Zend\Diactoros\Response;

class HttpCore implements ResponseFactoryInterface, RequestHandlerInterface
{
    /** @var HttpConfig */
    protected $config;

    /** @var Pipeline */
    protected $pipeline;

    /** @var ContainerInterface */
    protected $container;

    /** @var RequestHandlerInterface */
    protected $handler;

    /**
     * @param HttpConfig              $config
     * @param Pipeline                $pipeline
     * @param ContainerInterface|null $container
     */
    public function __construct(HttpConfig $config, Pipeline $pipeline, ContainerInterface $container = null)
    {
        $this->config = $config;
        $this->pipeline = $pipeline;
        $this->container = $container;

        foreach ($this->config->baseMiddleware() as $middleware) {
            $this->pipeline->pushMiddleware($this->container->get($middleware));
        }
    }

    /**
     * @param RequestHandlerInterface|callable $handler
     * @return HttpCore
     */
    public function setHandler($handler): self
    {
        if ($handler instanceof RequestHandlerInterface) {
            $this->handler = $handler;
        } elseif (is_callable($handler)) {
            $this->handler = new CallableHandler($handler, $this);
        } else {
            throw new HttpException("Invalid handler is given, expects callable or RequestHandlerInterface.");
        }

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     *
     * @throws HttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->target)) {
            throw new HttpException("Unable to run HttpCore, no handler is set.");
        }

        return $this->pipeline->withHandler($this->handler)->handle($request);
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
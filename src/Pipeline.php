<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Core\ScopeInterface;
use Spiral\Http\Exceptions\PipelineException;
use Spiral\Http\Traits\JsonTrait;
use Spiral\Http\Traits\MiddlewaresTrait;

/**
 * Pipeline used to pass request and response thought the chain of middlewares.
 *
 * Spiral middlewares are similar to Laravel's one. However router and http itself
 * can be in used in zend expressive.
 */
class Pipeline implements RequestHandlerInterface, MiddlewareInterface
{
    use MiddlewaresTrait, JsonTrait;

    /** @var ScopeInterface */
    private $scope;

    /** @var int */
    private $position = 0;

    /** @var RequestHandlerInterface */
    private $target;

    /**
     * @param ScopeInterface $scope
     */
    public function __construct(ScopeInterface $scope)
    {
        $this->scope = $scope;
    }

    /**
     * Configures pipeline with target endpoint.
     *
     * @param RequestHandlerInterface $target
     *
     * @return Pipeline
     *
     * @throws PipelineException
     */
    public function withHandler(RequestHandlerInterface $target): self
    {
        if ($this->position !== 0) {
            throw new PipelineException("Unable to set pipeline target, pipeline has been started.");
        }

        $pipeline = clone $this;
        $pipeline->target = $target;

        return $pipeline;
    }

    /**
     * Run pipeline in Middleware mode (wraps next middleware as pipeline target).
     *
     * @param Request                 $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return $this->withHandler($handler)->handle($request);
    }

    /**
     * @inheritdoc
     */
    public function handle(Request $request): Response
    {
        if (empty($this->target)) {
            throw new PipelineException("Unable to run pipeline without given target.");
        }

        $position = $this->position++;
        if (isset($this->middlewares[$position])) {
            return $this->middlewares[$position]->process($request, $this);
        }

        return $this->scope->runScope([Request::class => $request], function () use ($request) {
            return $this->target->handle($request);
        });
    }
}
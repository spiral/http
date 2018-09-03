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
use Spiral\Http\Traits\MiddlewaresTrait;

/**
 * Pipeline used to pass request and response thought the chain of middlewares.
 *
 * Spiral middlewares are similar to Laravel's one. However router and http itself
 * can be in used in zend expressive.
 */
class Pipeline implements RequestHandlerInterface, MiddlewareInterface
{
    use MiddlewaresTrait;

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
        $pipeline = clone $this;
        $pipeline->target = $target;
        $pipeline->position = 0;

        return $pipeline;
    }

    /**
     * @inheritdoc
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
            throw new PipelineException("Unable to run pipeline, no handler given.");
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
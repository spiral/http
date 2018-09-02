<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http;

use Psr\Http\Message\ResponseFactoryInterface;
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

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var int */
    private $position = 0;

    /** @var callable|RequestHandlerInterface */
    private $target;

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param ScopeInterface           $scope
     */
    public function __construct(ResponseFactoryInterface $responseFactory, ScopeInterface $scope)
    {
        $this->scope = $scope;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Configures pipeline with target endpoint.
     *
     * @param callable|RequestHandlerInterface $target
     *
     * @return Pipeline
     *
     * @throws PipelineException
     */
    public function withTarget($target): self
    {
        if ($this->position !== 0) {
            throw new PipelineException("Unable to set pipeline target, pipeline has been started.");
        }

        if (!is_callable($target) && !$target instanceof RequestHandlerInterface) {
            throw new PipelineException("Target must be callable or instance of RequestHandlerInterface.");
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
        return $this->withTarget($handler)->handle($request);
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

        if ($this->target instanceof RequestHandlerInterface) {
            return $this->target->handle($request);
        }

        return $this->invokeTarget(
            $request,
            $this->responseFactory->createResponse(200)
        );
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     * @throws \Throwable
     */
    protected function invokeTarget(Request $request, Response $response): Response
    {
        $outputLevel = ob_get_level();
        ob_start();

        $output = $result = null;

        try {
            $result = $this->scope->runScope([
                Request::class  => $request,
                Response::class => $response,
            ], $this->target);
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        } finally {
            while (ob_get_level() > $outputLevel + 1) {
                $output = ob_get_clean() . $output;
            }
        }

        return $this->wrapResponse($response, $result, ob_get_clean() . $output);
    }

    /**
     * Convert endpoint result into valid response.
     *
     * @param Response $response Initial pipeline response.
     * @param mixed    $result   Generated endpoint output.
     * @param string   $output   Buffer output.
     *
     * @return Response
     */
    private function wrapResponse(Response $response, $result = null, string $output = ''): Response
    {
        if ($result instanceof Response) {
            if (!empty($output) && $result->getBody()->isWritable()) {
                $result->getBody()->write($output);
            }

            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            $response = $this->writeJson($response, $result);
        } else {
            $response->getBody()->write($result);
        }

        //Always glue buffered output
        $response->getBody()->write($output);

        return $response;
    }
}
<?php

declare(strict_types=1);

namespace Spiral\Tests\Http;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Core\ContainerScope;
use Spiral\Core\ScopeInterface;
use Spiral\Http\CallableHandler;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\CurrentRequest;
use Spiral\Http\Event\MiddlewareProcessing;
use Spiral\Http\Exception\PipelineException;
use Spiral\Http\Pipeline;
use Spiral\Telemetry\NullTracer;
use Spiral\Testing\Attribute\TestScope;
use Spiral\Tests\Http\Diactoros\ResponseFactory;
use Nyholm\Psr7\ServerRequest;

final class PipelineTest extends TestCase
{
    public function testTarget(): void
    {
        $pipeline = new Pipeline($this->container);

        $handler = new CallableHandler(function () {
            return 'response';
        }, new ResponseFactory(new HttpConfig(['headers' => []])));

        $response = $pipeline->withHandler($handler)->handle(new ServerRequest('GET', ''));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }

    public function testHandle(): void
    {
        $pipeline = new Pipeline($this->container);

        $handler = new CallableHandler(function () {
            return 'response';
        }, new ResponseFactory(new HttpConfig(['headers' => []])));

        $response = $pipeline->process(new ServerRequest('GET', ''), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }

    public function testHandleException(): void
    {
        $this->expectException(PipelineException::class);

        $pipeline = new Pipeline($this->container);
        $pipeline->handle(new ServerRequest('GET', ''));
    }

    public function testMiddlewareProcessingEventShouldBeDispatched(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return (new ResponseFactory(new HttpConfig(['headers' => []])))->createResponse(200);
            }
        };
        $request = new ServerRequest('GET', '');
        $handler = new CallableHandler(function () {
            return 'response';
        }, new ResponseFactory(new HttpConfig(['headers' => []])));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new MiddlewareProcessing($request, $middleware));

        $pipeline = new Pipeline($this->container, $dispatcher, new NullTracer($this->container));

        $pipeline->pushMiddleware($middleware);

        $pipeline->withHandler($handler)->handle($request);
    }

    public function testRequestResetThroughPipeline(): void
    {
        $this->container->getBinder('http')
            ->bindSingleton(CurrentRequest::class, new CurrentRequest());
        $this->container->getBinder('http')
            ->bind(ServerRequestInterface::class, static fn(CurrentRequest $cr) => $cr->get());

        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $cRequest = ContainerScope::getContainer()->get(ServerRequestInterface::class);
                PipelineTest::assertSame($cRequest, $request);

                $response = $handler->handle($request->withAttribute('foo', 'bar'));

                $cRequest = ContainerScope::getContainer()->get(ServerRequestInterface::class);
                PipelineTest::assertSame($cRequest, $request);
                return $response;
            }
        };

        $this->container->runScope(
            new \Spiral\Core\Scope(name: 'http'),
            function (ScopeInterface $c) use ($middleware) {
                $request = new ServerRequest('GET', '');
                $handler = new CallableHandler(function () {
                    return 'response';
                }, new ResponseFactory(new HttpConfig(['headers' => []])));

                $pipeline = new Pipeline($c, null, new NullTracer($c));

                $pipeline->pushMiddleware($middleware);
                $pipeline->pushMiddleware($middleware);

                $pipeline->withHandler($handler)->handle($request);
            }
        );
    }
}

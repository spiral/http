<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Http\CallableHandler;
use Spiral\Http\HttpCore;
use Spiral\Http\Pipeline;
use Zend\Diactoros\ServerRequest;

class PipelineTest extends TestCase
{
    public function testTarget()
    {
        $pipeline = new Pipeline(new Container());

        $handler = new CallableHandler(function () {
            return "response";
        }, new HttpCore(new Container()));

        $response = $pipeline->withHandler($handler)->handle(new ServerRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('response', (string)$response->getBody());
    }
}
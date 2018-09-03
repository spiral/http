<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Files\Files;
use Spiral\Http\Response\ResponseWrapper;
use Zend\Diactoros\Response;

class ResponseWrapperTest extends TestCase
{
    public function testRedirect()
    {
        $response = $this->getWrapper()->redirect('google.com');
        $this->assertSame('google.com', $response->getHeaderLine('Location'));
        $this->assertSame(302, $response->getStatusCode());

        $response = $this->getWrapper()->redirect('google.com', 301);
        $this->assertSame('google.com', $response->getHeaderLine('Location'));
        $this->assertSame(301, $response->getStatusCode());
    }

    /**
     * @expectedException \Spiral\Http\Exceptions\ResponseException
     */
    public function testRedirectException()
    {
        $this->getWrapper()->redirect(true);
    }

    public function testJson()
    {
        $response = $this->getWrapper()->json([
            'status'  => 300,
            'message' => 'hi'
        ]);

        $this->assertSame('{"status":300,"message":"hi"}', (string)$response->getBody());
        $this->assertSame(300, $response->getStatusCode());
    }

    public function testHtml()
    {
        $response = $this->getWrapper()->html('hello world');
        $this->assertSame('hello world', (string)$response->getBody());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(["text/html; charset=UTF-8"], $response->getHeader("Content-Type"));
    }

    public function testAttachment()
    {
        $response = $this->getWrapper()->attachment(__FILE__);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentStream()
    {
        $response = $this->getWrapper()->attachment(fopen(__FILE__, 'r'), 'file.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    protected function getWrapper(): ResponseWrapper
    {
        return new ResponseWrapper(
            new WrapperResponseFactory(),
            new Files()
        );
    }
}

class WrapperResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response('php://memory', $code, []))->withStatus($code, $reasonPhrase);
    }
}
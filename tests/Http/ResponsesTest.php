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
use Psr\Http\Message\StreamInterface;
use Spiral\Files\Files;
use Spiral\Http\Json\JsonEncoder;
use Spiral\Http\Response\Response;
use Spiral\Http\Response\ResponseWrapper;
use Spiral\Http\Stream;
use Spiral\Streams\StreamableInterface;


class ResponsesTest extends TestCase
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
     * @expectedException \Spiral\Http\Exception\ResponseException
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

        $this->assertSame('{"status":300,"message":"hi"}', (string)$response->getBody()->getContents());
        $this->assertSame(300, $response->getStatusCode());
    }

    public function testHtml()
    {
        $response = $this->getWrapper()->html('hello world');
        $this->assertSame('hello world', (string)$response->getBody()->getContents());
        $this->assertSame(200, $response->getStatusCode());
        $ff = $response->getHeader("Content-Type");
        $this->assertSame(["text/html; charset=utf-8"], $response->getHeader("Content-Type"));
    }

    public function testAttachment()
    {
        $response = $this->getWrapper()->attachment(__FILE__);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentResource()
    {
        $response = $this->getWrapper()->attachment(fopen(__FILE__, 'r'), 'file.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentStream()
    {
        $response = $this->getWrapper()->attachment(new Stream(fopen(__FILE__, 'r'), 'r'), 'file.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentStreamable()
    {
        $response = $this->getWrapper()->attachment(
            new Streamable(new Stream(fopen(__FILE__, 'r'), 'r')),
            'file.php'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    /**
     * @expectedException \Spiral\Http\Exception\ResponseException
     */
    public function testAttachmentStreamNoName()
    {
        $response = $this->getWrapper()->attachment(new Stream(fopen(__FILE__, 'r'), 'r'));
    }

    /**
     * @expectedException \Spiral\Http\Exception\ResponseException
     */
    public function testAttachmentException()
    {
        $response = $this->getWrapper()->attachment('invalid');
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

class Streamable implements StreamableInterface
{
    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }
}

<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Spiral\Files\Files;
use Spiral\Http\Config\HttpConfig;
use Spiral\Http\ResponseWrapper;
use Spiral\Http\Tests\Diactoros\ResponseFactory;
use Spiral\Http\Tests\Diactoros\StreamFactory;
use Spiral\Streams\StreamableInterface;
use Zend\Diactoros\Stream;

class ResponsesTest extends TestCase
{
    public function testRedirect(): void
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
    public function testRedirectException(): void
    {
        $this->getWrapper()->redirect(true);
    }

    public function testJson(): void
    {
        $response = $this->getWrapper()->json([
            'status'  => 300,
            'message' => 'hi'
        ]);

        $this->assertSame('{"status":300,"message":"hi"}', (string)$response->getBody());
        $this->assertSame(300, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHtml(): void
    {
        $response = $this->getWrapper()->html('hello world');
        $this->assertSame('hello world', (string)$response->getBody());
        $this->assertSame(200, $response->getStatusCode());
        $ff = $response->getHeader('Content-Type');
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    public function testAttachment(): void
    {
        $response = $this->getWrapper()->attachment(__FILE__);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentResource(): void
    {
        $response = $this->getWrapper()->attachment(fopen(__FILE__, 'r'), 'file.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentStream(): void
    {
        $response = $this->getWrapper()->attachment(new Stream(fopen(__FILE__, 'r'), 'r'), 'file.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), (string)$response->getBody());
        $this->assertSame(filesize(__FILE__), $response->getBody()->getSize());
        $this->assertSame('application/octet-stream', (string)$response->getHeaderLine('Content-Type'));
    }

    public function testAttachmentStreamable(): void
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
    public function testAttachmentStreamNoName(): void
    {
        $response = $this->getWrapper()->attachment(new Stream(fopen(__FILE__, 'r'), 'r'));
    }

    /**
     * @expectedException \Spiral\Http\Exception\ResponseException
     */
    public function testAttachmentException(): void
    {
        $response = $this->getWrapper()->attachment('invalid');
    }

    protected function getWrapper(): ResponseWrapper
    {
        return new ResponseWrapper(
            new ResponseFactory(new HttpConfig(['headers' => []])),
            new StreamFactory(),
            new Files()
        );
    }
}

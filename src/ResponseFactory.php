<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\Config\HttpConfig;

final class ResponseFactory implements ResponseFactoryInterface
{
    /** @var HttpConfig */
    private $config;

    /** @var \Psr\Http\Message\ResponseInterface */
    private $response;

    /**
     * ResponseFactory constructor.
     *
     * @param \Spiral\Http\Config\HttpConfig      $config
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(HttpConfig $config, ResponseInterface $response)
    {
        $this->config = $config;
        $this->response = $response;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = $this->response->withBody(new Stream('php://memory', 'rw'));
        $response = $response->withStatus($code, $reasonPhrase);

        foreach ($this->config->baseHeaders() as $header => $value) {
            $response = $response->withAddedHeader($header, $value);
        }

        return $response;
    }
}
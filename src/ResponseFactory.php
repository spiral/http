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
use Spiral\Http\Response\Response;

final class ResponseFactory implements ResponseFactoryInterface
{
    /** @var HttpConfig */
    private $config;

    /**
     * ResponseFactory constructor.
     *
     * @param \Spiral\Http\Config\HttpConfig $config
     */
    public function __construct(HttpConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new Response('php://memory', $code, []);
        $response = $response->withStatus($code, $reasonPhrase);

        foreach ($this->config->baseHeaders() as $header => $value) {
            $response = $response->withAddedHeader($header, $value);
        }

        return $response;
    }
}
<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http\Traits;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\Response\JsonResponse;

/**
 * Provides ability to write json payloads into responses.
 */
trait JsonTrait
{
    /**
     * Generate JSON response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param                                     $json
     * @param int                                 $code
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function writeJson(
        ResponseInterface $response,
        $json,
        int $code = 200
    ): ResponseInterface {
        if ($json instanceof JsonSerializable) {
            $json = $json->jsonSerialize();
        }

        if (is_array($json) && isset($json['status'])) {
            $code = $json['status'];
        }

        return new JsonResponse($json, $code, $response->getHeaders());
    }
}

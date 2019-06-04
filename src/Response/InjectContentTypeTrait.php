<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Sergey Telpuk
 */
declare(strict_types=1);

namespace Spiral\Http\Response;

use function array_keys;
use function array_reduce;
use function strtolower;

trait InjectContentTypeTrait
{
    /**
     * Inject the provided Content-Type, if none is already present.
     *
     * @param string $contentType
     * @param array  $headers
     *
     * @return array Headers with injected Content-Type
     */
    private function injectContentType($contentType, array $headers)
    {
        $hasContentType = array_reduce(array_keys($headers), function ($carry, $item) {
            return $carry ?: (strtolower($item) === 'content-type');
        }, false);

        if (!$hasContentType) {
            $headers['content-type'] = [$contentType];
        }

        return $headers;
    }
}

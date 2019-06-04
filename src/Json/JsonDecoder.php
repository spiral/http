<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Sergey Telpuk
 */
declare(strict_types=1);

namespace Spiral\Http\Json;

use InvalidArgumentException;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use const JSON_ERROR_NONE;

final class JsonDecoder
{
    /**
     * @param string $data
     * @param bool   $assoc
     * @param int    $depth
     * @param int    $options
     *
     * @return mixed
     */
    public function decode(string $data, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $json = json_decode($data, $assoc, $depth, $options);
        $jsonLastError = json_last_error();

        if ($jsonLastError !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Failed to decode JSON: ' . json_last_error_msg(), $jsonLastError);
        }

        return $json;
    }
}

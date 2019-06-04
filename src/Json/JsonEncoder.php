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
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use const JSON_ERROR_NONE;

final class JsonEncoder
{
    /**
     * @param array $data
     * @param int   $options
     * @param int   $depth
     *
     * @return string
     */
    public function encode(array $data, int $options = 0, int $depth = 512): string
    {
        $json = json_encode($data, $options, $depth);
        $jsonLastError = json_last_error();

        if ($jsonLastError !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Failed to encode JSON: ' . json_last_error_msg(), $jsonLastError);
        }

        return $json;
    }
}
<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Http\Exception\ClientException;

use Spiral\Http\Exception\ClientException;

/**
 * HTTP 401 exception.
 */
class UnauthenticatedException extends ClientException
{
    /** @var int */
    protected $code = ClientException::UNAUTHENTICATED;

    /**
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($this->code, $message, $previous);
    }
}

<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Exceptions\ClientExceptions;

use Spiral\Http\Exceptions\ClientException;

/**
 * HTTP 401 exception.
 */
class UnauthorizedException extends ClientException
{
    /** @var int */
    protected $code = ClientException::UNAUTHORIZED;

    /**
     * @param string $message
     */
    public function __construct(string $message = "")
    {
        parent::__construct($this->code, $message);
    }
}
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
 * HTTP 500 exception.
 */
class ServerErrorException extends ClientException
{
    /** @var int */
    protected $code = ClientException::ERROR;

    /**
     * @param string $message
     */
    public function __construct(string $message = '')
    {
        parent::__construct($this->code, $message);
    }
}
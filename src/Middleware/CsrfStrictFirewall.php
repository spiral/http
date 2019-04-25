<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http\Middleware;

/**
 * Requires CSRF token to presented in every passed request.
 */
class CsrfStrictFirewall extends CsrfFirewall
{
    const ALLOW_METHODS = [];
}